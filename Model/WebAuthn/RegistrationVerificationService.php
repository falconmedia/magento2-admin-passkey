<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use CBOR\CBOREncoder;
use CBOR\Types\CBORByteString;
use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterfaceFactory;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\UserVerification;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Security-critical WebAuthn registration (attestation) verification.
 *
 * Follows the W3C registration ceremony: validate clientDataJSON (type, challenge,
 * origin), consume the single-use challenge before persisting, verify the
 * authenticator data (rpIdHash, user presence/verification, attested credential
 * data) and the COSE public key, reject duplicate credentials, then persist the
 * credential and audit the outcome.
 *
 * Attestation statements are accepted as "none" (the norm for passkeys). When a
 * non-none fmt is present its value is recorded for diagnostics, but the
 * attestation CA chain is intentionally NOT validated (see ADR 0001) — the
 * challenge/origin/rpId binding and signature during assertion are what protect
 * the login. This is an explicit, documented trade-off for Admin-only passkeys.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RegistrationVerificationService implements RegistrationVerificationServiceInterface
{
    public function __construct(
        private readonly ChallengeGuard $challengeGuard,
        private readonly ClientDataParser $clientDataParser,
        private readonly AuthenticatorDataParser $authenticatorDataParser,
        private readonly CoseKeyConverter $coseKeyConverter,
        private readonly RelyingPartyProvider $relyingParty,
        private readonly ConfigProvider $configProvider,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly CredentialInterfaceFactory $credentialFactory,
        private readonly Base64UrlEncoder $base64UrlEncoder,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly Json $json,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function verify(int $adminUserId, array $attestationResponse, ?string $remoteIp = null): CredentialInterface
    {
        try {
            $credential = $this->doVerify($adminUserId, $attestationResponse, $remoteIp);
            $this->auditSuccess($credential, $remoteIp);

            return $credential;
        } catch (WebAuthnVerificationException $e) {
            $this->auditFailure($adminUserId, $e->getMessage(), $remoteIp);
            throw $e;
        }
    }

    /**
     * Run the full registration verification and persist the credential.
     *
     * @param int $adminUserId
     * @param array<string,mixed> $attestationResponse
     * @param string|null $remoteIp
     * @return CredentialInterface
     * @throws WebAuthnVerificationException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function doVerify(int $adminUserId, array $attestationResponse, ?string $remoteIp): CredentialInterface
    {
        $credentialId = $this->requireString($attestationResponse, 'id');
        $response = $attestationResponse['response'] ?? null;
        if (!is_array($response)) {
            throw new WebAuthnVerificationException(__('The passkey response is incomplete.'));
        }

        $rawClientData = $this->base64UrlEncoder->decode($this->requireString($response, 'clientDataJSON'));
        $clientData = $this->clientDataParser->parse($rawClientData);

        $challenge = $this->challengeGuard->loadPending(
            ChallengeInterface::TYPE_REGISTRATION,
            $clientData['challenge'],
            $adminUserId
        );
        $this->clientDataParser->assertMatches(
            $clientData,
            ClientDataParser::TYPE_CREATE,
            (string) $challenge->getChallenge(),
            $this->relyingParty->getOrigin()
        );

        // Consume the challenge before persisting the credential (single-use guarantee).
        $this->challengeGuard->consume($challenge);

        $attestationObject = $this->decodeAttestationObject($this->requireString($response, 'attestationObject'));
        $authData = $this->extractAuthData($attestationObject);
        $authenticatorData = $this->authenticatorDataParser->parse($authData);

        $this->assertRpIdHash($authenticatorData);
        $this->assertUserFlags($authenticatorData);
        if (!$authenticatorData->hasAttestedCredentialData()) {
            throw new WebAuthnVerificationException(__('The passkey attestation is missing required data.'));
        }
        $this->assertCredentialIdMatches($credentialId, (string) $authenticatorData->getCredentialId());

        $coseKey = (string) $authenticatorData->getCoseKey();
        // Validate the public key is a supported, parseable COSE key before storing it.
        $this->coseKeyConverter->getAlgorithm($coseKey);
        $this->coseKeyConverter->toPem($coseKey);

        $this->assertNotAlreadyRegistered($credentialId);

        return $this->persistCredential(
            $adminUserId,
            $credentialId,
            $coseKey,
            $authenticatorData,
            $this->stringValue($attestationObject['fmt'] ?? null),
            $this->extractTransports($response)
        );
    }

    /**
     * Decode the base64url attestation object into its CBOR map.
     *
     * @param string $encodedAttestationObject
     * @return array<int|string,mixed>
     * @throws WebAuthnVerificationException
     */
    private function decodeAttestationObject(string $encodedAttestationObject): array
    {
        $binary = $this->base64UrlEncoder->decode($encodedAttestationObject);
        if ($binary === '') {
            throw new WebAuthnVerificationException(__('The passkey attestation object is missing.'));
        }

        $buffer = $binary;
        try {
            // CBOREncoder::decode() consumes $buffer by reference.
            $decoded = CBOREncoder::decode($buffer);
        } catch (\Throwable $e) {
            throw new WebAuthnVerificationException(__('The passkey attestation object could not be read.'), $e);
        }

        if (!is_array($decoded)) {
            throw new WebAuthnVerificationException(__('The passkey attestation object could not be read.'));
        }

        return $decoded;
    }

    /**
     * Extract the raw authenticator data bytes from the attestation object.
     *
     * @param array<int|string,mixed> $attestationObject
     * @return string
     * @throws WebAuthnVerificationException
     */
    private function extractAuthData(array $attestationObject): string
    {
        $authData = $attestationObject['authData'] ?? null;
        if ($authData instanceof CBORByteString) {
            $authData = $authData->get_byte_string();
        }
        if (!is_string($authData) || $authData === '') {
            throw new WebAuthnVerificationException(__('The passkey attestation is missing required data.'));
        }

        return $authData;
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
     * Assert the attested credential id matches the response credential id.
     *
     * @param string $responseCredentialId Base64url credential id from the response.
     * @param string $attestedCredentialId Raw credential id from authenticator data.
     * @return void
     * @throws WebAuthnVerificationException
     */
    private function assertCredentialIdMatches(string $responseCredentialId, string $attestedCredentialId): void
    {
        if (!hash_equals($this->base64UrlEncoder->decode($responseCredentialId), $attestedCredentialId)) {
            throw new WebAuthnVerificationException(__('The passkey credential does not match the attestation.'));
        }
    }

    /**
     * Reject registration when an active or revoked credential id already exists.
     *
     * @param string $credentialId Base64url credential id.
     * @return void
     * @throws WebAuthnVerificationException
     */
    private function assertNotAlreadyRegistered(string $credentialId): void
    {
        try {
            $this->credentialRepository->getByCredentialId($credentialId);
        } catch (NoSuchEntityException) {
            return;
        }

        throw new WebAuthnVerificationException(__('This passkey is already registered.'));
    }

    /**
     * Build and persist the active credential row.
     *
     * @param int $adminUserId
     * @param string $credentialId
     * @param string $coseKey
     * @param AuthenticatorData $authenticatorData
     * @param string $fmt
     * @param string[] $transports
     * @return CredentialInterface
     * @throws WebAuthnVerificationException
     */
    private function persistCredential(
        int $adminUserId,
        string $credentialId,
        string $coseKey,
        AuthenticatorData $authenticatorData,
        string $fmt,
        array $transports
    ): CredentialInterface {
        /** @var CredentialInterface $credential */
        $credential = $this->credentialFactory->create();
        $credential->setAdminUserId($adminUserId)
            ->setCredentialId($credentialId)
            ->setPublicKey($this->base64UrlEncoder->encode($coseKey))
            ->setSignCount($authenticatorData->getSignCount())
            ->setTransports($transports === [] ? null : $this->json->serialize($transports))
            ->setDeviceMetadata($this->buildDeviceMetadata($authenticatorData, $fmt))
            ->setStatus(CredentialInterface::STATUS_ACTIVE);

        try {
            return $this->credentialRepository->save($credential);
        } catch (\Throwable $e) {
            // The unique credential_id constraint is the backstop against a race.
            throw new WebAuthnVerificationException(__('This passkey is already registered.'), $e);
        }
    }

    /**
     * Build the non-sensitive device metadata JSON string.
     *
     * @param AuthenticatorData $authenticatorData
     * @param string $fmt
     * @return string
     */
    private function buildDeviceMetadata(AuthenticatorData $authenticatorData, string $fmt): string
    {
        $aaguid = $authenticatorData->getAaguid();

        return $this->json->serialize([
            'aaguid' => $aaguid !== null && $aaguid !== '' ? bin2hex($aaguid) : null,
            'attestation_format' => $fmt !== '' ? $fmt : 'none',
        ]);
    }

    /**
     * Extract the reported authenticator transports from the response.
     *
     * @param array<string,mixed> $response
     * @return string[]
     */
    private function extractTransports(array $response): array
    {
        $transports = $response['transports'] ?? null;
        if (!is_array($transports)) {
            return [];
        }

        $result = [];
        foreach ($transports as $transport) {
            if (is_string($transport) && $transport !== '') {
                $result[] = $transport;
            }
        }

        return $result;
    }

    /**
     * Record a successful registration audit event; never break the flow on failure.
     *
     * @param CredentialInterface $credential
     * @param string|null $remoteIp
     * @return void
     */
    private function auditSuccess(CredentialInterface $credential, ?string $remoteIp): void
    {
        $this->audit(
            (int) $credential->getAdminUserId(),
            [
                AuditLoggerInterface::CONTEXT_METADATA => [
                    'result' => 'success',
                    'credential_row_id' => $credential->getId(),
                ],
            ],
            $remoteIp
        );
    }

    /**
     * Record a failed registration audit event; never break the flow on failure.
     *
     * @param int $adminUserId
     * @param string $reason
     * @param string|null $remoteIp
     * @return void
     */
    private function auditFailure(int $adminUserId, string $reason, ?string $remoteIp): void
    {
        $this->audit(
            $adminUserId,
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
     * Record a passkey-registration audit event, swallowing any audit error.
     *
     * @param int $targetAdminUserId
     * @param array<string,mixed> $context
     * @param string|null $remoteIp
     * @return void
     */
    private function audit(int $targetAdminUserId, array $context, ?string $remoteIp): void
    {
        $context[AuditLoggerInterface::CONTEXT_TARGET] = $targetAdminUserId;
        if ($remoteIp !== null && $remoteIp !== '') {
            $context[AuditLoggerInterface::CONTEXT_IP] = $remoteIp;
        }

        try {
            $this->auditLogger->record(AuditLoggerInterface::EVENT_PASSKEY_REGISTRATION, $context);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to record passkey registration audit event: ' . $e->getMessage(),
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

    /**
     * Cast a decoded value to a string, tolerating null.
     *
     * @param mixed $value
     * @return string
     */
    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
