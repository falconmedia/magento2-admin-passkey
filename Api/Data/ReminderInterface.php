<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Migration reminder data interface.
 *
 * Each row records one reminder sent (or attempted) to an administrator.
 * metadata holds a JSON string and must never contain raw secrets.
 */
interface ReminderInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const REMINDER_TYPE = 'reminder_type';
    public const STATUS = 'status';
    public const SENT_AT = 'sent_at';
    public const METADATA = 'metadata';
    public const CREATED_AT = 'created_at';

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public const TYPE_MIGRATION_PASSKEY = 'migration_passkey';

    /**
     * Get reminder row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set reminder row ID.
     *
     * @param int|null $id
     * @return ReminderInterface
     */
    public function setId(?int $id): ReminderInterface;

    /**
     * Get target admin user ID.
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int;

    /**
     * Set target admin user ID.
     *
     * @param int|null $adminUserId
     * @return ReminderInterface
     */
    public function setAdminUserId(?int $adminUserId): ReminderInterface;

    /**
     * Get reminder type code.
     *
     * @return string|null
     */
    public function getReminderType(): ?string;

    /**
     * Set reminder type code.
     *
     * @param string|null $reminderType
     * @return ReminderInterface
     */
    public function setReminderType(?string $reminderType): ReminderInterface;

    /**
     * Get status (sent|failed).
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status (sent|failed).
     *
     * @param string|null $status
     * @return ReminderInterface
     */
    public function setStatus(?string $status): ReminderInterface;

    /**
     * Get sent at timestamp.
     *
     * @return string|null
     */
    public function getSentAt(): ?string;

    /**
     * Set sent at timestamp.
     *
     * @param string|null $sentAt
     * @return ReminderInterface
     */
    public function setSentAt(?string $sentAt): ReminderInterface;

    /**
     * Get non-sensitive metadata (JSON string).
     *
     * @return string|null
     */
    public function getMetadata(): ?string;

    /**
     * Set non-sensitive metadata (JSON string).
     *
     * @param string|null $metadata
     * @return ReminderInterface
     */
    public function setMetadata(?string $metadata): ReminderInterface;

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
     * @return ReminderInterface
     */
    public function setCreatedAt(?string $createdAt): ReminderInterface;
}
