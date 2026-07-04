<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;

/**
 * Builds publicKeyCredentialRequestOptions and persists an assertion challenge.
 *
 * Supports discoverable credentials (empty allowCredentials when no admin user is
 * given) and known credentials (allowCredentials populated from a specific admin
 * user's active credentials, including transports).
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class AssertionChallengeService implements AssertionChallengeServiceInterface
{
    public function __construct(
        private readonly ChallengeIssuer $challengeIssuer,
        private readonly RelyingPartyProvider $relyingParty,
        private readonly CredentialDescriptors $credentialDescriptors,
        private readonly ConfigProvider $configProvider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function createOptions(?int $adminUserId = null, ?string $remoteIp = null): array
    {
        $challenge = $this->challengeIssuer->issue(
            ChallengeInterface::TYPE_ASSERTION,
            $adminUserId,
            $remoteIp
        );

        $allowCredentials = $adminUserId !== null
            ? $this->credentialDescriptors->forAdmin($adminUserId)
            : [];

        return [
            'challenge' => $challenge,
            'timeout' => $this->configProvider->getCeremonyTimeoutMs(),
            'rpId' => $this->relyingParty->getId(),
            'userVerification' => $this->configProvider->getUserVerification(),
            'allowCredentials' => $allowCredentials,
        ];
    }
}
