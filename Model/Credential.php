<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\Credential as CredentialResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Passkey credential entity.
 */
class Credential extends AbstractModel implements CredentialInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(CredentialResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        $value = $this->getData(self::ENTITY_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setId($id): CredentialInterface
    {
        return $this->setData(self::ENTITY_ID, $id === null ? null : (int) $id);
    }

    /**
     * @inheritdoc
     */
    public function getAdminUserId(): ?int
    {
        $value = $this->getData(self::ADMIN_USER_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setAdminUserId(?int $adminUserId): CredentialInterface
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getCredentialId(): ?string
    {
        $value = $this->getData(self::CREDENTIAL_ID);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setCredentialId(?string $credentialId): CredentialInterface
    {
        return $this->setData(self::CREDENTIAL_ID, $credentialId);
    }

    /**
     * @inheritdoc
     */
    public function getPublicKey(): ?string
    {
        $value = $this->getData(self::PUBLIC_KEY);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setPublicKey(?string $publicKey): CredentialInterface
    {
        return $this->setData(self::PUBLIC_KEY, $publicKey);
    }

    /**
     * @inheritdoc
     */
    public function getSignCount(): ?int
    {
        $value = $this->getData(self::SIGN_COUNT);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setSignCount(?int $signCount): CredentialInterface
    {
        return $this->setData(self::SIGN_COUNT, $signCount);
    }

    /**
     * @inheritdoc
     */
    public function getTransports(): ?string
    {
        $value = $this->getData(self::TRANSPORTS);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setTransports(?string $transports): CredentialInterface
    {
        return $this->setData(self::TRANSPORTS, $transports);
    }

    /**
     * @inheritdoc
     */
    public function getFriendlyName(): ?string
    {
        $value = $this->getData(self::FRIENDLY_NAME);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setFriendlyName(?string $friendlyName): CredentialInterface
    {
        return $this->setData(self::FRIENDLY_NAME, $friendlyName);
    }

    /**
     * @inheritdoc
     */
    public function getDeviceMetadata(): ?string
    {
        $value = $this->getData(self::DEVICE_METADATA);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setDeviceMetadata(?string $deviceMetadata): CredentialInterface
    {
        return $this->setData(self::DEVICE_METADATA, $deviceMetadata);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        $value = $this->getData(self::STATUS);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?string $status): CredentialInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): CredentialInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(?string $updatedAt): CredentialInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getLastUsedAt(): ?string
    {
        $value = $this->getData(self::LAST_USED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setLastUsedAt(?string $lastUsedAt): CredentialInterface
    {
        return $this->setData(self::LAST_USED_AT, $lastUsedAt);
    }

    /**
     * @inheritdoc
     */
    public function getRevokedAt(): ?string
    {
        $value = $this->getData(self::REVOKED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setRevokedAt(?string $revokedAt): CredentialInterface
    {
        return $this->setData(self::REVOKED_AT, $revokedAt);
    }
}
