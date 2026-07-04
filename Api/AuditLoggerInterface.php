<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api;

use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * High-level audit recording service.
 *
 * This is the contract business logic should use to record a security event. It
 * standardises event-type codes and severity mapping, then delegates persistence
 * to {@see AuditLogInterface}. Metadata must never contain raw secrets.
 */
interface AuditLoggerInterface
{
    /**
     * Standardised event-type codes.
     */
    public const EVENT_PASSKEY_REGISTRATION = 'passkey_registration';
    public const EVENT_PASSKEY_LOGIN = 'passkey_login';
    public const EVENT_PASSWORD_LOGIN = 'password_login';
    public const EVENT_LOGIN_FAILED = 'login_failed';
    public const EVENT_PASSKEY_REVOKE = 'passkey_revoke';
    public const EVENT_PASSKEY_NAME_UPDATED = 'passkey_name_updated';
    public const EVENT_TRUSTED_DEVICE_CREATED = 'trusted_device_created';
    public const EVENT_TRUSTED_DEVICE_REVOKE = 'trusted_device_revoke';
    public const EVENT_TRUSTED_DEVICE_EXPIRED = 'trusted_device_expired';
    public const EVENT_LOCKOUT = 'lockout';
    public const EVENT_UNLOCK = 'unlock';
    public const EVENT_BRUTE_FORCE = 'brute_force_detected';
    public const EVENT_RECOVERY_ENABLE = 'recovery_enable';
    public const EVENT_RECOVERY_DISABLE = 'recovery_disable';
    public const EVENT_DIAGNOSTICS_GENERATE = 'diagnostics_generate';
    public const EVENT_DIAGNOSTICS_SEND = 'diagnostics_send';
    public const EVENT_CLEANUP = 'cleanup';
    public const EVENT_MIGRATION_REMINDER = 'migration_reminder';
    public const EVENT_SECURITY_SCORE_SNAPSHOT = 'security_score_snapshot';
    public const EVENT_CONFIG_CHANGE = 'config_change';

    /**
     * Optional context keys accepted by {@see AuditLoggerInterface::record()}.
     * Any value not supplied is resolved automatically where possible.
     */
    public const CONTEXT_ACTOR = 'actor_admin_user_id';
    public const CONTEXT_TARGET = 'target_admin_user_id';
    public const CONTEXT_SEVERITY = 'severity';
    public const CONTEXT_IP = 'ip';
    public const CONTEXT_USER_AGENT = 'user_agent';
    public const CONTEXT_METADATA = 'metadata';
    public const CONTEXT_SUPPORT_REFERENCE_ID = 'support_reference_id';

    /**
     * Record a security audit event.
     *
     * Severity defaults to the standard mapping for the given event type unless
     * an explicit severity is passed in the context. Actor, IP and user agent are
     * resolved from the current admin session/request when not supplied. Metadata
     * is sanitised of known secret keys before being stored as JSON.
     *
     * @param string $eventType One of the EVENT_* codes.
     * @param array<string,mixed> $context Optional CONTEXT_* overrides; metadata must be a non-sensitive assoc array.
     * @return AuditEventInterface The persisted audit event.
     * @throws CouldNotSaveException
     */
    public function record(string $eventType, array $context = []): AuditEventInterface;

    /**
     * Resolve the standard severity for an event-type code.
     *
     * @param string $eventType
     * @return string One of the AuditEventInterface::SEVERITY_* values.
     */
    public function resolveSeverity(string $eventType): string;
}
