<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\UserVerification;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Security-critical WebAuthn assertion (authentication) verification.
 *
 * Follows the W3C assertion ceremony: validate clientDataJSON (type, challenge,
 * origin), consume the single-use challenge before signature verification (so a
 * consumed challenge can never be replayed), resolve and status-check the
 * credential, verify the authenticator data (rpIdHash, user presence/verification)
 * and the signature over authenticatorData||SHA-256(clientDataJSON), reject
 * sign-counter regression (clone/replay), then update last_used_at + sign_count
 * and audit the outcome. Never creates a session — returns an {@see AssertionResult}.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AssertionVerificationService implements AssertionVerificationServiceInterface
{
    public function __construct(
        private readonly ChallengeGuard $challengeGuard,
        private readonly ClientDataParser $clientDataParser,
        private readonly AuthenticatorDataParser $authenticatorDataParser,
        private readonly SignatureVerifier $signatureVerifier,
        private readonly RelyingPartyProvider $relyingParty,
        private readonly ConfigProvider $configProvider,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly Base64UrlEncoder $base64UrlEncoder,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function verify(array $assertionResponse, ?string $remoteIp = null): AssertionResult
    {
        try {
            $result = $this->doVerify($assertionResponse, $remoteIp);
            $this->auditSuccess($result, $remoteIp);

            return $result;
        } catch (WebAuthnVerificationException $e) {
            $this->auditFailure($e->getMessage(), $remoteIp);
            throw $e;
        }
    }

    /**
     * Run the full assertion verification.
     *
     * @param array<string,mixed> $assertionResponse
     * @param string|null $remoteIp
     * @return AssertionResult
     * @throws WebAuthnVerificationException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function doVerify(array $assertionResponse, ?string $remoteIp): AssertionResult
    {
        $credentialId = $this->requireString($assertionResponse, 'id');
        $response = $assertionResponse['response'] ?? null;
        if (!is_array($response)) {
            throw new WebAuthnVerificationException(__('The passkey response is incomplete.'));
        }

        $rawClientData = $this->base64UrlEncoder->decode($this->requireString($response, 'clientDataJSON'));
        $clientData = $this->clientDataParser->parse($rawClientData);

        $challenge = $this->challengeGuard->loadPending(
            ChallengeInterface::TYPE_ASSERTION,
            $clientData['challenge']
        );
        $this->clientDataParser->assertMatches(
            $clientData,
            ClientDataParser::TYPE_GET,
            (string) $challenge->getChallenge(),
            $this->relyingParty->getOrigin()
        );

        // Consume the challenge before verifying the signature (replay protection).
        $this->challengeGuard->consume($challenge);

        $credential = $this->resolveActiveCredential($credentialId);
        $this->assertUserBinding($challenge, $credential, $response);

        $authData = $this->base64UrlEncoder->decode($this->requireString($response, 'authenticatorData'));
        $authenticatorData = $this->authenticatorDataParser->parse($authData);
        $this->assertRpIdHash($authenticatorData);
        $this->assertUserFlags($authenticatorData);

        $this->assertSignature($credential, $authData, $rawClientData, $this->requireString($response, 'signature'));
        $this->assertSignCount($credential, $authenticatorData->getSignCount());

        $this->updateOnSuccess($credential, $authenticatorData->getSignCount());

        return new AssertionResult(true, (int) $credential->getAdminUserId(), $credentialId);
    }

    /**
     * Resolve an active credential by its base64url credential id.
     *
     * @param string $credentialId
     * @return CredentialInterface
     * @throws WebAuthnVerificationException
     */
    private function resolveActiveCredential(string $credentialId): CredentialInterface
    {
        try {
            $credential = $this->credentialRepository->getByCredentialId($credentialId);
        } catch (NoSuchEntityException $e) {
            throw new WebAuthnVerificationException(__('The passkey credential is not recognised.'), $e);
        }

        if ($credential->getStatus() !== CredentialInterface::STATUS_ACTIVE) {
            throw new WebAuthnVerificationException(__('The passkey credential is no longer active.'));
        }

        return $credential;
    }

    /**
     * Assert the credential is bound to the challenge's admin user and user handle.
     *
     * @param ChallengeInterface $challenge
     * @param CredentialInterface $credential
     * @param array<string,mixed> $response
     * @return void
     * @throws WebAuthnVerificationException
     */
    private function assertUserBinding(
        ChallengeInterface $challenge,
        CredentialInterface $credential,
        array $response
    ): void {
        $adminUserId = (int) $credential->getAdminUserId();

        if ($challenge->getAdminUserId() !== null && (int) $challenge->getAdminUserId() !== $adminUserId) {
            throw new WebAuthnVerificationException(__('The passkey challenge does not match the requested user.'));
        }

        $userHandle = $response['userHandle'] ?? null;
        if (is_string($userHandle) && $userHandle !== '') {
            $decoded = $this->base64UrlEncoder->decode($userHandle);
            if (!hash_equals((string) $adminUserId, $decoded)) {
                throw new WebAuthnVerificationException(__('The passkey user handle does not match.'));
            }
        }
    }

    /**
     * Assert the authenticator data rpIdHash matches the configured relying party.
     *
     * @param AuthenticatorData $authenticatorData
     * @return void
     * @throws WebAuthnVerificationException
     */
    private function assertRpIdHash(AuthenticatorData $authenticatorData): void
    {
        $expected = hash('sha256', $this->relyingParty->getId(), true);
        if (!hash_equals($expected, $authenticatorData->getRpIdHash())) {
            throw new WebAuthnVerificationException(__('The passkey relying party could not be verified.'));
        }
    }

    /**
     * Assert user-present and (when required) user-verified flags are set.
     *
     * @param AuthenticatorData $authenticatorData
     * @return void
     * @throws WebAuthnVerificationException
     */
    private function assertUserFlags(AuthenticatorData $authenticatorData): void
    {
        if (!$authenticatorData->isUserPresent()) {
            throw new WebAuthnVerificationException(__('User presence was not verified by the authenticator.'));
        }
        if ($this->configProvider->getUserVerification() === UserVerification::REQUIRED
            && !$authenticatorData->isUserVerified()
        ) {
            throw new WebAuthnVerificationException(__('User verification is required but was not performed.'));
        }
    }

    /**
     * Verify the assertion signature over authenticatorData||SHA-256(clientDataJSON).
     *
     * @param CredentialInterface $credential
     * @param string $authData Raw authenticator data bytes.
     * @param string $rawClientData Raw clientDataJSON bytes.
     * @param string $encodedSignature Base64url signature from the response.
     * @return void
     * @throws WebAuthnVerificationException
     */
    private function assertSignature(
        CredentialInterface $credential,
        string $authData,
        string $rawClientData,
        string $encodedSignature
    ): void {
        $signedData = $authData . hash('sha256', $rawClientData, true);
        $signature = $this->base64UrlEncoder->decode($encodedSignature);
        $coseKey = $this->base64UrlEncoder->decode((string) $credential->getPublicKey());

        if (!$this->signatureVerifier->verify($coseKey, $signedData, $signature)) {
            throw new WebAuthnVerificationException(__('The passkey signature is invalid.'));
        }
    }

    /**
     * Reject a sign-counter regression (clone/replay), allowing the both-zero case.
     *
     * @param CredentialInterface $credential
     * @param int $newSignCount
     * @return void
     * @throws WebAuthnVerificationException
     */
    private function assertSignCount(CredentialInterface $credential, int $newSignCount): void
    {
        $storedSignCount = (int) $credential->getSignCount();
        if ($newSignCount <= $storedSignCount && !($newSignCount === 0 && $storedSignCount === 0)) {
            throw new WebAuthnVerificationException(__('The passkey sign counter is invalid.'));
        }
    }

    /**
     * Persist the new sign count and last-used timestamp after a valid assertion.
     *
     * @param CredentialInterface $credential
     * @param int $newSignCount
     * @return void
     * @throws WebAuthnVerificationException
     */
    private function updateOnSuccess(CredentialInterface $credential, int $newSignCount): void
    {
        $credential->setSignCount($newSignCount);
        $credential->setLastUsedAt($this->dateTime->gmtDate());

        try {
            $this->credentialRepository->save($credential);
        } catch (\Throwable $e) {
            throw new WebAuthnVerificationException(__('The passkey login could not be completed.'), $e);
        }
    }

    /**
     * Record a successful login audit event; never break the flow on failure.
     *
     * @param AssertionResult $result
     * @param string|null $remoteIp
     * @return void
     */
    private function auditSuccess(AssertionResult $result, ?string $remoteIp): void
    {
        $this->audit(
            $result->getAdminUserId(),
            [
                AuditLoggerInterface::CONTEXT_METADATA => ['result' => 'success'],
            ],
            $remoteIp
        );
    }

    /**
     * Record a failed login audit event; never break the flow on failure.
     *
     * @param string $reason
     * @param string|null $remoteIp
     * @return void
     */
    private function auditFailure(string $reason, ?string $remoteIp): void
    {
        $this->audit(
            null,
            [
                AuditLoggerInterface::CONTEXT_SEVERITY => AuditEventInterface::SEVERITY_WARNING,
                AuditLoggerInterface::CONTEXT_METADATA => [
                    'result' => 'failure',
                    'reason' => $reason,
                ],
            ],
            $remoteIp
        );
    }

    /**
     * Record a passkey-login audit event, swallowing any audit error.
     *
     * @param int|null $targetAdminUserId
     * @param array<string,mixed> $context
     * @param string|null $remoteIp
     * @return void
     */
    private function audit(?int $targetAdminUserId, array $context, ?string $remoteIp): void
    {
        if ($targetAdminUserId !== null) {
            $context[AuditLoggerInterface::CONTEXT_TARGET] = $targetAdminUserId;
        }
        if ($remoteIp !== null && $remoteIp !== '') {
            $context[AuditLoggerInterface::CONTEXT_IP] = $remoteIp;
        }

        try {
            $this->auditLogger->record(AuditLoggerInterface::EVENT_PASSKEY_LOGIN, $context);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to record passkey login audit event: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Require a non-empty string field from an array.
     *
     * @param array<string,mixed> $data
     * @param string $key
     * @return string
     * @throws WebAuthnVerificationException
     */
    private function requireString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value) || $value === '') {
            throw new WebAuthnVerificationException(__('The passkey response is incomplete.'));
        }

        return $value;
    }
}
