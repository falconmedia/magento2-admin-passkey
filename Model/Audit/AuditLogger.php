<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Audit;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterfaceFactory;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\HTTP\Header as HttpHeader;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Standardises audit event codes/severity and delegates persistence to the
 * audit repository. Never stores raw secrets in metadata.
 */
class AuditLogger implements AuditLoggerInterface
{
    /**
     * Maximum stored user-agent length (matches the audit table column).
     */
    private const USER_AGENT_MAX_LENGTH = 512;

    /**
     * Event-type code => severity mapping.
     *
     * @var array<string, string>
     */
    private const SEVERITY_MAP = [
        self::EVENT_PASSKEY_REGISTRATION => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_PASSKEY_LOGIN => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_PASSWORD_LOGIN => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_LOGIN_FAILED => AuditEventInterface::SEVERITY_WARNING,
        self::EVENT_PASSKEY_REVOKE => AuditEventInterface::SEVERITY_NOTICE,
        self::EVENT_PASSKEY_NAME_UPDATED => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_TRUSTED_DEVICE_CREATED => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_TRUSTED_DEVICE_REVOKE => AuditEventInterface::SEVERITY_NOTICE,
        self::EVENT_TRUSTED_DEVICE_EXPIRED => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_LOCKOUT => AuditEventInterface::SEVERITY_WARNING,
        self::EVENT_UNLOCK => AuditEventInterface::SEVERITY_NOTICE,
        self::EVENT_BRUTE_FORCE => AuditEventInterface::SEVERITY_CRITICAL,
        self::EVENT_RECOVERY_ENABLE => AuditEventInterface::SEVERITY_CRITICAL,
        self::EVENT_RECOVERY_DISABLE => AuditEventInterface::SEVERITY_NOTICE,
        self::EVENT_DIAGNOSTICS_GENERATE => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_DIAGNOSTICS_SEND => AuditEventInterface::SEVERITY_NOTICE,
        self::EVENT_CLEANUP => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_MIGRATION_REMINDER => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_SECURITY_SCORE_SNAPSHOT => AuditEventInterface::SEVERITY_INFO,
        self::EVENT_CONFIG_CHANGE => AuditEventInterface::SEVERITY_NOTICE,
    ];

    /**
     * Case-insensitive substrings that mark a metadata key as sensitive. Matching
     * entries are redacted before storage as a defence-in-depth measure.
     *
     * @var string[]
     */
    private const SENSITIVE_KEY_MARKERS = [
        'password',
        'secret',
        'token',
        'private_key',
        'privatekey',
        'challenge',
        'credential_id',
        'assertion',
        'signature',
        'cookie',
        'authorization',
    ];

    private const REDACTED_PLACEHOLDER = '[redacted]';

    public function __construct(
        private readonly AuditLogInterface $auditLog,
        private readonly AuditEventInterfaceFactory $auditEventFactory,
        private readonly Json $json,
        private readonly RemoteAddress $remoteAddress,
        private readonly HttpHeader $httpHeader,
        private readonly AdminSession $adminSession
    ) {
    }

    /**
     * @inheritdoc
     */
    public function record(string $eventType, array $context = []): AuditEventInterface
    {
        /** @var AuditEventInterface $event */
        $event = $this->auditEventFactory->create();
        $event->setEventType($eventType);
        $event->setSeverity(
            $this->normaliseSeverity($context[self::CONTEXT_SEVERITY] ?? null) ?? $this->resolveSeverity($eventType)
        );
        $event->setActorAdminUserId(
            $this->toNullableInt($context[self::CONTEXT_ACTOR] ?? null) ?? $this->resolveActorId()
        );
        $event->setTargetAdminUserId($this->toNullableInt($context[self::CONTEXT_TARGET] ?? null));
        $event->setIp($this->toNullableString($context[self::CONTEXT_IP] ?? null) ?? $this->resolveIp());
        $event->setUserAgent(
            $this->truncateUserAgent(
                $this->toNullableString($context[self::CONTEXT_USER_AGENT] ?? null) ?? $this->resolveUserAgent()
            )
        );
        $event->setSupportReferenceId($this->toNullableString($context[self::CONTEXT_SUPPORT_REFERENCE_ID] ?? null));
        $event->setMetadata($this->buildMetadata($context[self::CONTEXT_METADATA] ?? null));

        return $this->auditLog->save($event);
    }

    /**
     * @inheritdoc
     */
    public function resolveSeverity(string $eventType): string
    {
        return self::SEVERITY_MAP[$eventType] ?? AuditEventInterface::SEVERITY_INFO;
    }

    /**
     * Build a sanitised JSON metadata string, or null when there is nothing to store.
     *
     * @param mixed $metadata
     * @return string|null
     */
    private function buildMetadata(mixed $metadata): ?string
    {
        if (!is_array($metadata) || $metadata === []) {
            return null;
        }

        $sanitised = $this->sanitiseMetadata($metadata);

        return $sanitised === [] ? null : $this->json->serialize($sanitised);
    }

    /**
     * Recursively redact values whose key looks sensitive.
     *
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    private function sanitiseMetadata(array $metadata): array
    {
        $result = [];
        foreach ($metadata as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $result[$key] = self::REDACTED_PLACEHOLDER;
                continue;
            }
            $result[$key] = is_array($value) ? $this->sanitiseMetadata($value) : $value;
        }

        return $result;
    }

    /**
     * Determine whether a metadata key name looks sensitive.
     *
     * @param string $key
     * @return bool
     */
    private function isSensitiveKey(string $key): bool
    {
        $needle = strtolower($key);
        foreach (self::SENSITIVE_KEY_MARKERS as $marker) {
            if (str_contains($needle, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the acting admin user id from the current session, if any.
     *
     * @return int|null
     */
    private function resolveActorId(): ?int
    {
        try {
            $user = $this->adminSession->getUser();
            $userId = $user?->getId();

            return $userId !== null ? (int) $userId : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the current request remote IP address.
     *
     * @return string|null
     */
    private function resolveIp(): ?string
    {
        try {
            $ip = (string) $this->remoteAddress->getRemoteAddress();

            return $ip === '' ? null : $ip;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the current request user-agent header.
     *
     * @return string|null
     */
    private function resolveUserAgent(): ?string
    {
        try {
            $userAgent = $this->httpHeader->getHttpUserAgent();

            return $userAgent === '' ? null : $userAgent;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Truncate a user-agent string to the stored column length.
     *
     * @param string|null $userAgent
     * @return string|null
     */
    private function truncateUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return mb_substr($userAgent, 0, self::USER_AGENT_MAX_LENGTH);
    }

    /**
     * Validate and normalise an explicit severity override.
     *
     * @param mixed $severity
     * @return string|null
     */
    private function normaliseSeverity(mixed $severity): ?string
    {
        if (!is_string($severity) || $severity === '') {
            return null;
        }

        $allowed = [
            AuditEventInterface::SEVERITY_INFO,
            AuditEventInterface::SEVERITY_NOTICE,
            AuditEventInterface::SEVERITY_WARNING,
            AuditEventInterface::SEVERITY_CRITICAL,
        ];

        return in_array($severity, $allowed, true) ? $severity : null;
    }

    /**
     * Cast a context value to a nullable integer.
     *
     * @param mixed $value
     * @return int|null
     */
    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Cast a context value to a nullable, non-empty string.
     *
     * @param mixed $value
     * @return string|null
     */
    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = (string) $value;

        return $string === '' ? null : $string;
    }
}
