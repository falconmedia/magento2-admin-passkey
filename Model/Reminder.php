<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\ReminderInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\Reminder as ReminderResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Migration reminder entity.
 */
class Reminder extends AbstractModel implements ReminderInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ReminderResource::class);
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
    public function setId($id): ReminderInterface
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
    public function setAdminUserId(?int $adminUserId): ReminderInterface
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    /**
     * @inheritdoc
     */
    public function getReminderType(): ?string
    {
        $value = $this->getData(self::REMINDER_TYPE);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setReminderType(?string $reminderType): ReminderInterface
    {
        return $this->setData(self::REMINDER_TYPE, $reminderType);
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
    public function setStatus(?string $status): ReminderInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getSentAt(): ?string
    {
        $value = $this->getData(self::SENT_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setSentAt(?string $sentAt): ReminderInterface
    {
        return $this->setData(self::SENT_AT, $sentAt);
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
    public function setMetadata(?string $metadata): ReminderInterface
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
    public function setCreatedAt(?string $createdAt): ReminderInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
