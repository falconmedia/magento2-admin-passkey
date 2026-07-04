<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\RecoveryState as RecoveryStateResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Recovery-state entity.
 */
class RecoveryState extends AbstractModel implements RecoveryStateInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(RecoveryStateResource::class);
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
    public function setId($id): RecoveryStateInterface
    {
        return $this->setData(self::ENTITY_ID, $id === null ? null : (int) $id);
    }

    /**
     * @inheritdoc
     */
    public function getState(): ?string
    {
        $value = $this->getData(self::STATE);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setState(?string $state): RecoveryStateInterface
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * @inheritdoc
     */
    public function getEnabledAt(): ?string
    {
        $value = $this->getData(self::ENABLED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setEnabledAt(?string $enabledAt): RecoveryStateInterface
    {
        return $this->setData(self::ENABLED_AT, $enabledAt);
    }

    /**
     * @inheritdoc
     */
    public function getDisabledAt(): ?string
    {
        $value = $this->getData(self::DISABLED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setDisabledAt(?string $disabledAt): RecoveryStateInterface
    {
        return $this->setData(self::DISABLED_AT, $disabledAt);
    }

    /**
     * @inheritdoc
     */
    public function getActorAdminUserId(): ?int
    {
        $value = $this->getData(self::ACTOR_ADMIN_USER_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setActorAdminUserId(?int $actorAdminUserId): RecoveryStateInterface
    {
        return $this->setData(self::ACTOR_ADMIN_USER_ID, $actorAdminUserId);
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
    public function setReason(?string $reason): RecoveryStateInterface
    {
        return $this->setData(self::REASON, $reason);
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
    public function setMetadata(?string $metadata): RecoveryStateInterface
    {
        return $this->setData(self::METADATA, $metadata);
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
    public function setUpdatedAt(?string $updatedAt): RecoveryStateInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
