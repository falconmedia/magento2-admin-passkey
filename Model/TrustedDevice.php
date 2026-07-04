<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\TrustedDevice as TrustedDeviceResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Trusted device entity.
 */
class TrustedDevice extends AbstractModel implements TrustedDeviceInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(TrustedDeviceResource::class);
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
    public function setId($id): TrustedDeviceInterface
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
    public function setAdminUserId(?int $adminUserId): TrustedDeviceInterface
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getDeviceTokenHash(): ?string
    {
        $value = $this->getData(self::DEVICE_TOKEN_HASH);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setDeviceTokenHash(?string $deviceTokenHash): TrustedDeviceInterface
    {
        return $this->setData(self::DEVICE_TOKEN_HASH, $deviceTokenHash);
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): ?string
    {
        $value = $this->getData(self::LABEL);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setLabel(?string $label): TrustedDeviceInterface
    {
        return $this->setData(self::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(): ?string
    {
        $value = $this->getData(self::METADATA);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setMetadata(?string $metadata): TrustedDeviceInterface
    {
        return $this->setData(self::METADATA, $metadata);
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
    public function setStatus(?string $status): TrustedDeviceInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getFirstSeenAt(): ?string
    {
        $value = $this->getData(self::FIRST_SEEN_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setFirstSeenAt(?string $firstSeenAt): TrustedDeviceInterface
    {
        return $this->setData(self::FIRST_SEEN_AT, $firstSeenAt);
    }

    /**
     * @inheritdoc
     */
    public function getLastSeenAt(): ?string
    {
        $value = $this->getData(self::LAST_SEEN_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setLastSeenAt(?string $lastSeenAt): TrustedDeviceInterface
    {
        return $this->setData(self::LAST_SEEN_AT, $lastSeenAt);
    }

    /**
     * @inheritdoc
     */
    public function getExpiresAt(): ?string
    {
        $value = $this->getData(self::EXPIRES_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setExpiresAt(?string $expiresAt): TrustedDeviceInterface
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
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
    public function setRevokedAt(?string $revokedAt): TrustedDeviceInterface
    {
        return $this->setData(self::REVOKED_AT, $revokedAt);
    }
}
