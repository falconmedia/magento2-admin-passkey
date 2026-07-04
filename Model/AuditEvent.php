<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\AuditEvent as AuditEventResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Security audit event entity.
 */
class AuditEvent extends AbstractModel implements AuditEventInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(AuditEventResource::class);
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
    public function setId($id): AuditEventInterface
    {
        return $this->setData(self::ENTITY_ID, $id === null ? null : (int) $id);
    }

    /**
     * @inheritdoc
     */
    public function getEventType(): ?string
    {
        $value = $this->getData(self::EVENT_TYPE);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setEventType(?string $eventType): AuditEventInterface
    {
        return $this->setData(self::EVENT_TYPE, $eventType);
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
    public function setActorAdminUserId(?int $actorAdminUserId): AuditEventInterface
    {
        return $this->setData(self::ACTOR_ADMIN_USER_ID, $actorAdminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getTargetAdminUserId(): ?int
    {
        $value = $this->getData(self::TARGET_ADMIN_USER_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setTargetAdminUserId(?int $targetAdminUserId): AuditEventInterface
    {
        return $this->setData(self::TARGET_ADMIN_USER_ID, $targetAdminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getSeverity(): ?string
    {
        $value = $this->getData(self::SEVERITY);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setSeverity(?string $severity): AuditEventInterface
    {
        return $this->setData(self::SEVERITY, $severity);
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
    public function setIp(?string $ip): AuditEventInterface
    {
        return $this->setData(self::IP, $ip);
    }

    /**
     * @inheritdoc
     */
    public function getUserAgent(): ?string
    {
        $value = $this->getData(self::USER_AGENT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setUserAgent(?string $userAgent): AuditEventInterface
    {
        return $this->setData(self::USER_AGENT, $userAgent);
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
    public function setMetadata(?string $metadata): AuditEventInterface
    {
        return $this->setData(self::METADATA, $metadata);
    }

    /**
     * @inheritdoc
     */
    public function getSupportReferenceId(): ?string
    {
        $value = $this->getData(self::SUPPORT_REFERENCE_ID);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setSupportReferenceId(?string $supportReferenceId): AuditEventInterface
    {
        return $this->setData(self::SUPPORT_REFERENCE_ID, $supportReferenceId);
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
    public function setCreatedAt(?string $createdAt): AuditEventInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
