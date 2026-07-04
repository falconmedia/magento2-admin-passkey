<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;

/**
 * Lightweight in-memory {@see CredentialInterface} implementation for unit tests,
 * so verification behaviour is exercised against real getters/setters rather than
 * a fully stubbed mock. Test support only (no {@code Test} suffix).
 *
 * @internal Test support only.
 */
class InMemoryCredential implements CredentialInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return isset($this->data[self::ENTITY_ID]) ? (int) $this->data[self::ENTITY_ID] : null;
    }

    /**
     * @inheritdoc
     */
    public function setId($id): CredentialInterface
    {
        $this->data[self::ENTITY_ID] = $id === null ? null : (int) $id;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAdminUserId(): ?int
    {
        return isset($this->data[self::ADMIN_USER_ID]) ? (int) $this->data[self::ADMIN_USER_ID] : null;
    }

    /**
     * @inheritdoc
     */
    public function setAdminUserId(?int $adminUserId): CredentialInterface
    {
        $this->data[self::ADMIN_USER_ID] = $adminUserId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCredentialId(): ?string
    {
        return $this->data[self::CREDENTIAL_ID] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setCredentialId(?string $credentialId): CredentialInterface
    {
        $this->data[self::CREDENTIAL_ID] = $credentialId;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPublicKey(): ?string
    {
        return $this->data[self::PUBLIC_KEY] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setPublicKey(?string $publicKey): CredentialInterface
    {
        $this->data[self::PUBLIC_KEY] = $publicKey;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSignCount(): ?int
    {
        return isset($this->data[self::SIGN_COUNT]) ? (int) $this->data[self::SIGN_COUNT] : null;
    }

    /**
     * @inheritdoc
     */
    public function setSignCount(?int $signCount): CredentialInterface
    {
        $this->data[self::SIGN_COUNT] = $signCount;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTransports(): ?string
    {
        return $this->data[self::TRANSPORTS] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setTransports(?string $transports): CredentialInterface
    {
        $this->data[self::TRANSPORTS] = $transports;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFriendlyName(): ?string
    {
        return $this->data[self::FRIENDLY_NAME] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setFriendlyName(?string $friendlyName): CredentialInterface
    {
        $this->data[self::FRIENDLY_NAME] = $friendlyName;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDeviceMetadata(): ?string
    {
        return $this->data[self::DEVICE_METADATA] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setDeviceMetadata(?string $deviceMetadata): CredentialInterface
    {
        $this->data[self::DEVICE_METADATA] = $deviceMetadata;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        return $this->data[self::STATUS] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?string $status): CredentialInterface
    {
        $this->data[self::STATUS] = $status;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->data[self::CREATED_AT] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): CredentialInterface
    {
        $this->data[self::CREATED_AT] = $createdAt;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->data[self::UPDATED_AT] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(?string $updatedAt): CredentialInterface
    {
        $this->data[self::UPDATED_AT] = $updatedAt;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLastUsedAt(): ?string
    {
        return $this->data[self::LAST_USED_AT] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setLastUsedAt(?string $lastUsedAt): CredentialInterface
    {
        $this->data[self::LAST_USED_AT] = $lastUsedAt;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRevokedAt(): ?string
    {
        return $this->data[self::REVOKED_AT] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setRevokedAt(?string $revokedAt): CredentialInterface
    {
        $this->data[self::REVOKED_AT] = $revokedAt;

        return $this;
    }
}
