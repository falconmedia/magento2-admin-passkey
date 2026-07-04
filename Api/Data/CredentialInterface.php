<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Passkey (WebAuthn) credential data interface.
 */
interface CredentialInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const CREDENTIAL_ID = 'credential_id';
    public const PUBLIC_KEY = 'public_key';
    public const SIGN_COUNT = 'sign_count';
    public const TRANSPORTS = 'transports';
    public const FRIENDLY_NAME = 'friendly_name';
    public const DEVICE_METADATA = 'device_metadata';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const LAST_USED_AT = 'last_used_at';
    public const REVOKED_AT = 'revoked_at';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    /**
     * Get credential row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set credential row ID.
     *
     * @param int|null $id
     * @return CredentialInterface
     */
    public function setId(?int $id): CredentialInterface;

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
     * @return CredentialInterface
     */
    public function setAdminUserId(?int $adminUserId): CredentialInterface;

    /**
     * Get WebAuthn credential ID (base64url).
     *
     * @return string|null
     */
    public function getCredentialId(): ?string;

    /**
     * Set WebAuthn credential ID (base64url).
     *
     * @param string|null $credentialId
     * @return CredentialInterface
     */
    public function setCredentialId(?string $credentialId): CredentialInterface;

    /**
     * Get COSE public key material.
     *
     * @return string|null
     */
    public function getPublicKey(): ?string;

    /**
     * Set COSE public key material.
     *
     * @param string|null $publicKey
     * @return CredentialInterface
     */
    public function setPublicKey(?string $publicKey): CredentialInterface;

    /**
     * Get WebAuthn signature counter.
     *
     * @return int|null
     */
    public function getSignCount(): ?int;

    /**
     * Set WebAuthn signature counter.
     *
     * @param int|null $signCount
     * @return CredentialInterface
     */
    public function setSignCount(?int $signCount): CredentialInterface;

    /**
     * Get authenticator transports.
     *
     * @return string|null
     */
    public function getTransports(): ?string;

    /**
     * Set authenticator transports.
     *
     * @param string|null $transports
     * @return CredentialInterface
     */
    public function setTransports(?string $transports): CredentialInterface;

    /**
     * Get user-defined passkey name.
     *
     * @return string|null
     */
    public function getFriendlyName(): ?string;

    /**
     * Set user-defined passkey name.
     *
     * @param string|null $friendlyName
     * @return CredentialInterface
     */
    public function setFriendlyName(?string $friendlyName): CredentialInterface;

    /**
     * Get non-sensitive device metadata (JSON string).
     *
     * @return string|null
     */
    public function getDeviceMetadata(): ?string;

    /**
     * Set non-sensitive device metadata (JSON string).
     *
     * @param string|null $deviceMetadata
     * @return CredentialInterface
     */
    public function setDeviceMetadata(?string $deviceMetadata): CredentialInterface;

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
     * @return CredentialInterface
     */
    public function setStatus(?string $status): CredentialInterface;

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
     * @return CredentialInterface
     */
    public function setCreatedAt(?string $createdAt): CredentialInterface;

    /**
     * Get updated at timestamp.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at timestamp.
     *
     * @param string|null $updatedAt
     * @return CredentialInterface
     */
    public function setUpdatedAt(?string $updatedAt): CredentialInterface;

    /**
     * Get last used at timestamp.
     *
     * @return string|null
     */
    public function getLastUsedAt(): ?string;

    /**
     * Set last used at timestamp.
     *
     * @param string|null $lastUsedAt
     * @return CredentialInterface
     */
    public function setLastUsedAt(?string $lastUsedAt): CredentialInterface;

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
     * @return CredentialInterface
     */
    public function setRevokedAt(?string $revokedAt): CredentialInterface;
}
