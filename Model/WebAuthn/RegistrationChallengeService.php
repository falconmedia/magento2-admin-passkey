<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\ResidentKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\User\Model\UserFactory;

/**
 * Builds publicKeyCredentialCreationOptions and persists a registration challenge.
 *
 * The option payload is a typed array (the representation the browser base64url-
 * decodes into ArrayBuffers). See ADR docs/admin-passkey/adr/0001-webauthn-reuse.md
 * for why options are built as arrays and verification reuses native primitives.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class RegistrationChallengeService implements RegistrationChallengeServiceInterface
{
    /**
     * COSE algorithm identifiers (RFC 8152) offered during registration.
     */
    private const ALG_ES256 = -7;
    private const ALG_RS256 = -257;

    public function __construct(
        private readonly ChallengeIssuer $challengeIssuer,
        private readonly RelyingPartyProvider $relyingParty,
        private readonly CredentialDescriptors $credentialDescriptors,
        private readonly ConfigProvider $configProvider,
        private readonly Base64UrlEncoder $base64UrlEncoder,
        private readonly UserFactory $userFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function createOptions(int $adminUserId, ?string $remoteIp = null): array
    {
        $user = $this->userFactory->create()->load($adminUserId);
        if (!$user->getId()) {
            throw new LocalizedException(__('The requested admin user does not exist.'));
        }

        $challenge = $this->challengeIssuer->issue(
            ChallengeInterface::TYPE_REGISTRATION,
            $adminUserId,
            $remoteIp
        );

        $username = (string) $user->getUserName();
        $displayName = trim((string) $user->getFirstName() . ' ' . (string) $user->getLastName());

        return [
            'rp' => [
                'id' => $this->relyingParty->getId(),
                'name' => $this->relyingParty->getName(),
            ],
            'user' => [
                'id' => $this->base64UrlEncoder->encode((string) $adminUserId),
                'name' => $username,
                'displayName' => $displayName !== '' ? $displayName : $username,
            ],
            'challenge' => $challenge,
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => self::ALG_ES256],
                ['type' => 'public-key', 'alg' => self::ALG_RS256],
            ],
            'authenticatorSelection' => $this->buildAuthenticatorSelection(),
            'timeout' => $this->configProvider->getCeremonyTimeoutMs(),
            'attestation' => 'none',
            'excludeCredentials' => $this->credentialDescriptors->forAdmin($adminUserId),
        ];
    }

    /**
     * Build the authenticatorSelection block from configuration.
     *
     * @return array<string, mixed>
     */
    private function buildAuthenticatorSelection(): array
    {
        $residentKey = $this->configProvider->getResidentKey();

        return [
            'residentKey' => $residentKey,
            'requireResidentKey' => $residentKey === ResidentKey::REQUIRED,
            'userVerification' => $this->configProvider->getUserVerification(),
        ];
    }
}
