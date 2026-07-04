<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\Lockout as LockoutResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Lockout entity.
 */
class Lockout extends AbstractModel implements LockoutInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(LockoutResource::class);
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
    public function setId($id): LockoutInterface
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
    public function setAdminUserId(?int $adminUserId): LockoutInterface
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getUsername(): ?string
    {
        $value = $this->getData(self::USERNAME);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setUsername(?string $username): LockoutInterface
    {
        return $this->setData(self::USERNAME, $username);
    }

    /**
     * @inheritdoc
     */
    public function getIp(): ?string
    {
        $value = $this->getData(self::IP);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setIp(?string $ip): LockoutInterface
    {
        return $this->setData(self::IP, $ip);
    }

    /**
     * @inheritdoc
     */
    public function getReason(): ?string
    {
        $value = $this->getData(self::REASON);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setReason(?string $reason): LockoutInterface
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * @inheritdoc
     */
    public function getFailedAttempts(): ?int
    {
        $value = $this->getData(self::FAILED_ATTEMPTS);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setFailedAttempts(?int $failedAttempts): LockoutInterface
    {
        return $this->setData(self::FAILED_ATTEMPTS, $failedAttempts);
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
    public function setStatus(?string $status): LockoutInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getLockedUntil(): ?string
    {
        $value = $this->getData(self::LOCKED_UNTIL);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setLockedUntil(?string $lockedUntil): LockoutInterface
    {
        return $this->setData(self::LOCKED_UNTIL, $lockedUntil);
    }

    /**
     * @inheritdoc
     */
    public function getUnlockedAt(): ?string
    {
        $value = $this->getData(self::UNLOCKED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setUnlockedAt(?string $unlockedAt): LockoutInterface
    {
        return $this->setData(self::UNLOCKED_AT, $unlockedAt);
    }

    /**
     * @inheritdoc
     */
    public function getUnlockedBy(): ?int
    {
        $value = $this->getData(self::UNLOCKED_BY);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setUnlockedBy(?int $unlockedBy): LockoutInterface
    {
        return $this->setData(self::UNLOCKED_BY, $unlockedBy);
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
    public function setMetadata(?string $metadata): LockoutInterface
    {
        return $this->setData(self::METADATA, $metadata);
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
    public function setCreatedAt(?string $createdAt): LockoutInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
