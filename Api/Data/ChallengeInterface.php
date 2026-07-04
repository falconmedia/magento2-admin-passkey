<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * WebAuthn challenge data interface.
 */
interface ChallengeInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const CHALLENGE = 'challenge';
    public const CHALLENGE_TYPE = 'challenge_type';
    public const STATUS = 'status';
    public const REMOTE_IP = 'remote_ip';
    public const EXPIRES_AT = 'expires_at';
    public const CONSUMED_AT = 'consumed_at';
    public const CREATED_AT = 'created_at';

    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_ASSERTION = 'assertion';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Get challenge row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set challenge row ID.
     *
     * @param int|null $id
     * @return ChallengeInterface
     */
    public function setId(?int $id): ChallengeInterface;

    /**
     * Get target admin user ID.
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int;

    /**
     * Set target admin user ID.
     *
     * @param int|null $adminUserId
     * @return ChallengeInterface
     */
    public function setAdminUserId(?int $adminUserId): ChallengeInterface;

    /**
     * Get challenge value (base64url).
     *
     * @return string|null
     */
    public function getChallenge(): ?string;

    /**
     * Set challenge value (base64url).
     *
     * @param string|null $challenge
     * @return ChallengeInterface
     */
    public function setChallenge(?string $challenge): ChallengeInterface;

    /**
     * Get challenge type (registration|assertion).
     *
     * @return string|null
     */
    public function getChallengeType(): ?string;

    /**
     * Set challenge type.
     *
     * @param string|null $challengeType
     * @return ChallengeInterface
     */
    public function setChallengeType(?string $challengeType): ChallengeInterface;

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status.
     *
     * @param string|null $status
     * @return ChallengeInterface
     */
    public function setStatus(?string $status): ChallengeInterface;

    /**
     * Get remote IP address.
     *
     * @return string|null
     */
    public function getRemoteIp(): ?string;

    /**
     * Set remote IP address.
     *
     * @param string|null $remoteIp
     * @return ChallengeInterface
     */
    public function setRemoteIp(?string $remoteIp): ChallengeInterface;

    /**
     * Get expiry timestamp.
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string;

    /**
     * Set expiry timestamp.
     *
     * @param string|null $expiresAt
     * @return ChallengeInterface
     */
    public function setExpiresAt(?string $expiresAt): ChallengeInterface;

    /**
     * Get consumed at timestamp.
     *
     * @return string|null
     */
    public function getConsumedAt(): ?string;

    /**
     * Set consumed at timestamp.
     *
     * @param string|null $consumedAt
     * @return ChallengeInterface
     */
    public function setConsumedAt(?string $consumedAt): ChallengeInterface;

    /**
     * Get created at timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at timestamp.
     *
     * @param string|null $createdAt
     * @return ChallengeInterface
     */
    public function setCreatedAt(?string $createdAt): ChallengeInterface;
}
