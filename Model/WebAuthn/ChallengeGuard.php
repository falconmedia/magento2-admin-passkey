<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Loads, validates and atomically consumes single-use WebAuthn challenges.
 *
 * A challenge is only accepted when it exists, is still pending (not already
 * consumed — which is what defeats replay), has not expired, and matches the
 * expected ceremony type and (for registration) the expected admin user. The
 * lookup is by the exact base64url challenge value echoed in clientDataJSON.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class ChallengeGuard
{
    public function __construct(
        private readonly ChallengeRepositoryInterface $challengeRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * Load a pending, unexpired challenge for the given type and value.
     *
     * @param string $type One of ChallengeInterface::TYPE_*.
     * @param string $challengeValue Base64url challenge value from clientDataJSON.
     * @param int|null $expectedAdminUserId When set, the challenge must target this admin user.
     * @return ChallengeInterface
     * @throws WebAuthnVerificationException
     */
    public function loadPending(string $type, string $challengeValue, ?int $expectedAdminUserId = null): ChallengeInterface
    {
        if ($challengeValue === '') {
            throw new WebAuthnVerificationException(__('The passkey challenge is invalid.'));
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ChallengeInterface::CHALLENGE, $challengeValue)
            ->addFilter(ChallengeInterface::CHALLENGE_TYPE, $type)
            ->create();

        $items = $this->challengeRepository->getList($searchCriteria)->getItems();
        $challenge = $items[array_key_first($items)] ?? null;

        if (!$challenge instanceof ChallengeInterface) {
            throw new WebAuthnVerificationException(__('The passkey challenge is invalid.'));
        }
        if ($challenge->getStatus() !== ChallengeInterface::STATUS_PENDING) {
            throw new WebAuthnVerificationException(__('The passkey challenge has already been used.'));
        }
        if ($this->isExpired($challenge)) {
            throw new WebAuthnVerificationException(__('The passkey challenge has expired.'));
        }
        if ($expectedAdminUserId !== null
            && $challenge->getAdminUserId() !== null
            && (int) $challenge->getAdminUserId() !== $expectedAdminUserId
        ) {
            throw new WebAuthnVerificationException(__('The passkey challenge does not match the requested user.'));
        }

        return $challenge;
    }

    /**
     * Mark the challenge consumed so it can never be reused.
     *
     * @param ChallengeInterface $challenge
     * @return void
     * @throws WebAuthnVerificationException
     */
    public function consume(ChallengeInterface $challenge): void
    {
        $id = $challenge->getId();
        if ($id === null) {
            throw new WebAuthnVerificationException(__('The passkey challenge is invalid.'));
        }

        try {
            $this->challengeRepository->consume($id);
        } catch (\Throwable $e) {
            throw new WebAuthnVerificationException(__('The passkey challenge could not be consumed.'), $e);
        }
    }

    /**
     * Whether the challenge expiry timestamp is in the past.
     *
     * @param ChallengeInterface $challenge
     * @return bool
     */
    private function isExpired(ChallengeInterface $challenge): bool
    {
        $expiresAt = $challenge->getExpiresAt();
        if ($expiresAt === null || $expiresAt === '') {
            return true;
        }

        $expiry = strtotime($expiresAt . ' UTC');
        if ($expiry === false) {
            return true;
        }

        return $expiry < $this->dateTime->gmtTimestamp();
    }
}
