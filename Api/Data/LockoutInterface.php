<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Lockout data interface.
 */
interface LockoutInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const USERNAME = 'username';
    public const IP = 'ip';
    public const REASON = 'reason';
    public const FAILED_ATTEMPTS = 'failed_attempts';
    public const STATUS = 'status';
    public const LOCKED_UNTIL = 'locked_until';
    public const UNLOCKED_AT = 'unlocked_at';
    public const UNLOCKED_BY = 'unlocked_by';
    public const METADATA = 'metadata';
    public const CREATED_AT = 'created_at';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RELEASED = 'released';

    /**
     * Get lockout row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set lockout row ID.
     *
     * @param int|null $id
     * @return LockoutInterface
     */
    public function setId(?int $id): LockoutInterface;

    /**
     * Get locked admin user ID.
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int;

    /**
     * Set locked admin user ID.
     *
     * @param int|null $adminUserId
     * @return LockoutInterface
     */
    public function setAdminUserId(?int $adminUserId): LockoutInterface;

    /**
     * Get locked username.
     *
     * @return string|null
     */
    public function getUsername(): ?string;

    /**
     * Set locked username.
     *
     * @param string|null $username
     * @return LockoutInterface
     */
    public function setUsername(?string $username): LockoutInterface;

    /**
     * Get remote IP address associated with the lockout.
     *
     * @return string|null
     */
    public function getIp(): ?string;

    /**
     * Set remote IP address associated with the lockout.
     *
     * @param string|null $ip
     * @return LockoutInterface
     */
    public function setIp(?string $ip): LockoutInterface;

    /**
     * Get lockout reason.
     *
     * @return string|null
     */
    public function getReason(): ?string;

    /**
     * Set lockout reason.
     *
     * @param string|null $reason
     * @return LockoutInterface
     */
    public function setReason(?string $reason): LockoutInterface;

    /**
     * Get failed attempt count.
     *
     * @return int|null
     */
    public function getFailedAttempts(): ?int;

    /**
     * Set failed attempt count.
     *
     * @param int|null $failedAttempts
     * @return LockoutInterface
     */
    public function setFailedAttempts(?int $failedAttempts): LockoutInterface;

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
     * @return LockoutInterface
     */
    public function setStatus(?string $status): LockoutInterface;

    /**
     * Get locked until timestamp.
     *
     * @return string|null
     */
    public function getLockedUntil(): ?string;

    /**
     * Set locked until timestamp.
     *
     * @param string|null $lockedUntil
     * @return LockoutInterface
     */
    public function setLockedUntil(?string $lockedUntil): LockoutInterface;

    /**
     * Get unlocked at timestamp.
     *
     * @return string|null
     */
    public function getUnlockedAt(): ?string;

    /**
     * Set unlocked at timestamp.
     *
     * @param string|null $unlockedAt
     * @return LockoutInterface
     */
    public function setUnlockedAt(?string $unlockedAt): LockoutInterface;

    /**
     * Get admin user ID that unlocked.
     *
     * @return int|null
     */
    public function getUnlockedBy(): ?int;

    /**
     * Set admin user ID that unlocked.
     *
     * @param int|null $unlockedBy
     * @return LockoutInterface
     */
    public function setUnlockedBy(?int $unlockedBy): LockoutInterface;

    /**
     * Get non-sensitive lockout metadata (JSON string).
     *
     * @return string|null
     */
    public function getMetadata(): ?string;

    /**
     * Set non-sensitive lockout metadata (JSON string).
     *
     * @param string|null $metadata
     * @return LockoutInterface
     */
    public function setMetadata(?string $metadata): LockoutInterface;

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
     * @return LockoutInterface
     */
    public function setCreatedAt(?string $createdAt): LockoutInterface;
}
