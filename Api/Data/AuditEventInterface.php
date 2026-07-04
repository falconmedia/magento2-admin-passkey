<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

/**
 * Security audit event data interface.
 * Metadata must never contain raw secrets.
 */
interface AuditEventInterface
{
    public const ENTITY_ID = 'entity_id';
    public const EVENT_TYPE = 'event_type';
    public const ACTOR_ADMIN_USER_ID = 'actor_admin_user_id';
    public const TARGET_ADMIN_USER_ID = 'target_admin_user_id';
    public const SEVERITY = 'severity';
    public const IP = 'ip';
    public const USER_AGENT = 'user_agent';
    public const METADATA = 'metadata';
    public const SUPPORT_REFERENCE_ID = 'support_reference_id';
    public const CREATED_AT = 'created_at';

    public const SEVERITY_INFO = 'info';
    public const SEVERITY_NOTICE = 'notice';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Get audit event row ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set audit event row ID.
     *
     * @param int|null $id
     * @return AuditEventInterface
     */
    public function setId(?int $id): AuditEventInterface;

    /**
     * Get event type code.
     *
     * @return string|null
     */
    public function getEventType(): ?string;

    /**
     * Set event type code.
     *
     * @param string|null $eventType
     * @return AuditEventInterface
     */
    public function setEventType(?string $eventType): AuditEventInterface;

    /**
     * Get actor admin user ID.
     *
     * @return int|null
     */
    public function getActorAdminUserId(): ?int;

    /**
     * Set actor admin user ID.
     *
     * @param int|null $actorAdminUserId
     * @return AuditEventInterface
     */
    public function setActorAdminUserId(?int $actorAdminUserId): AuditEventInterface;

    /**
     * Get target admin user ID.
     *
     * @return int|null
     */
    public function getTargetAdminUserId(): ?int;

    /**
     * Set target admin user ID.
     *
     * @param int|null $targetAdminUserId
     * @return AuditEventInterface
     */
    public function setTargetAdminUserId(?int $targetAdminUserId): AuditEventInterface;

    /**
     * Get severity.
     *
     * @return string|null
     */
    public function getSeverity(): ?string;

    /**
     * Set severity.
     *
     * @param string|null $severity
     * @return AuditEventInterface
     */
    public function setSeverity(?string $severity): AuditEventInterface;

    /**
     * Get remote IP address.
     *
     * @return string|null
     */
    public function getIp(): ?string;

    /**
     * Set remote IP address.
     *
     * @param string|null $ip
     * @return AuditEventInterface
     */
    public function setIp(?string $ip): AuditEventInterface;

    /**
     * Get user agent string.
     *
     * @return string|null
     */
    public function getUserAgent(): ?string;

    /**
     * Set user agent string.
     *
     * @param string|null $userAgent
     * @return AuditEventInterface
     */
    public function setUserAgent(?string $userAgent): AuditEventInterface;

    /**
     * Get non-sensitive event metadata (JSON string).
     *
     * @return string|null
     */
    public function getMetadata(): ?string;

    /**
     * Set non-sensitive event metadata (JSON string).
     *
     * @param string|null $metadata
     * @return AuditEventInterface
     */
    public function setMetadata(?string $metadata): AuditEventInterface;

    /**
     * Get related support reference ID.
     *
     * @return string|null
     */
    public function getSupportReferenceId(): ?string;

    /**
     * Set related support reference ID.
     *
     * @param string|null $supportReferenceId
     * @return AuditEventInterface
     */
    public function setSupportReferenceId(?string $supportReferenceId): AuditEventInterface;

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
     * @return AuditEventInterface
     */
    public function setCreatedAt(?string $createdAt): AuditEventInterface;
}
