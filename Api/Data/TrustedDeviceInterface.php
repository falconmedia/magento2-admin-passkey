<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Trusted device data interface.
 * device_token_hash stores a hash only, never the raw token.
 */
interface TrustedDeviceInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const DEVICE_TOKEN_HASH = 'device_token_hash';
    public const LABEL = 'label';
    public const METADATA = 'metadata';
    public const STATUS = 'status';
    public const FIRST_SEEN_AT = 'first_seen_at';
    public const LAST_SEEN_AT = 'last_seen_at';
    public const EXPIRES_AT = 'expires_at';
    public const REVOKED_AT = 'revoked_at';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Get trusted device row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set trusted device row ID.
     *
     * @param int|null $id
     * @return TrustedDeviceInterface
     */
    public function setId(?int $id): TrustedDeviceInterface;

    /**
     * Get owner admin user ID.
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int;

    /**
     * Set owner admin user ID.
     *
     * @param int|null $adminUserId
     * @return TrustedDeviceInterface
     */
    public function setAdminUserId(?int $adminUserId): TrustedDeviceInterface;

    /**
     * Get device token hash.
     *
     * @return string|null
     */
    public function getDeviceTokenHash(): ?string;

    /**
     * Set device token hash.
     *
     * @param string|null $deviceTokenHash
     * @return TrustedDeviceInterface
     */
    public function setDeviceTokenHash(?string $deviceTokenHash): TrustedDeviceInterface;

    /**
     * Get device label.
     *
     * @return string|null
     */
    public function getLabel(): ?string;

    /**
     * Set device label.
     *
     * @param string|null $label
     * @return TrustedDeviceInterface
     */
    public function setLabel(?string $label): TrustedDeviceInterface;

    /**
     * Get non-sensitive device metadata (JSON string).
     *
     * @return string|null
     */
    public function getMetadata(): ?string;

    /**
     * Set non-sensitive device metadata (JSON string).
     *
     * @param string|null $metadata
     * @return TrustedDeviceInterface
     */
    public function setMetadata(?string $metadata): TrustedDeviceInterface;

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
     * @return TrustedDeviceInterface
     */
    public function setStatus(?string $status): TrustedDeviceInterface;

    /**
     * Get first seen at timestamp.
     *
     * @return string|null
     */
    public function getFirstSeenAt(): ?string;

    /**
     * Set first seen at timestamp.
     *
     * @param string|null $firstSeenAt
     * @return TrustedDeviceInterface
     */
    public function setFirstSeenAt(?string $firstSeenAt): TrustedDeviceInterface;

    /**
     * Get last seen at timestamp.
     *
     * @return string|null
     */
    public function getLastSeenAt(): ?string;

    /**
     * Set last seen at timestamp.
     *
     * @param string|null $lastSeenAt
     * @return TrustedDeviceInterface
     */
    public function setLastSeenAt(?string $lastSeenAt): TrustedDeviceInterface;

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
     * @return TrustedDeviceInterface
     */
    public function setExpiresAt(?string $expiresAt): TrustedDeviceInterface;

    /**
     * Get revoked at timestamp.
     *
     * @return string|null
     */
    public function getRevokedAt(): ?string;

    /**
     * Set revoked at timestamp.
     *
     * @param string|null $revokedAt
     * @return TrustedDeviceInterface
     */
    public function setRevokedAt(?string $revokedAt): TrustedDeviceInterface;
}
