<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterfaceFactory;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Generates a cryptographically secure, base64url-encoded challenge and persists
 * a single-use, expiring challenge row through the repository contract.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class ChallengeIssuer
{
    /**
     * Number of random bytes in the raw challenge (WebAuthn requires at least 16).
     */
    private const CHALLENGE_BYTES = 32;

    public function __construct(
        private readonly ChallengeRepositoryInterface $challengeRepository,
        private readonly ChallengeInterfaceFactory $challengeFactory,
        private readonly ConfigProvider $configProvider,
        private readonly Base64UrlEncoder $base64UrlEncoder,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * Issue and persist a new challenge, returning its base64url representation.
     *
     * @param string $type One of ChallengeInterface::TYPE_*.
     * @param int|null $adminUserId Target admin user id (null for discoverable assertions).
     * @param string|null $remoteIp Optional remote IP to record.
     * @return string The base64url-encoded challenge value.
     * @throws CouldNotSaveException
     */
    public function issue(string $type, ?int $adminUserId, ?string $remoteIp): string
    {
        $challengeValue = $this->base64UrlEncoder->encode(random_bytes(self::CHALLENGE_BYTES));

        $lifetime = $this->configProvider->getChallengeLifetimeSeconds();
        $expiresAt = $this->dateTime->gmtDate('Y-m-d H:i:s', $this->dateTime->gmtTimestamp() + $lifetime);

        /** @var ChallengeInterface $challenge */
        $challenge = $this->challengeFactory->create();
        $challenge->setAdminUserId($adminUserId)
            ->setChallenge($challengeValue)
            ->setChallengeType($type)
            ->setStatus(ChallengeInterface::STATUS_PENDING)
            ->setRemoteIp($remoteIp)
            ->setExpiresAt($expiresAt);

        $this->challengeRepository->save($challenge);

        return $challengeValue;
    }
}
