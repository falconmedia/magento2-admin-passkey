<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Login;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Thin, no-op-safe login-outcome recorder.
 *
 * Responsibilities in Step 11:
 *  - Feed a rate-limit/lockout attempt counter on every failure (the seam Step 13
 *    will build proper lockout evaluation on top of).
 *  - Audit the password fallback path, which is otherwise unaudited. The passkey
 *    path is already audited by {@see \FalconMedia\AdminPasskey\Model\WebAuthn\AssertionVerificationService},
 *    so this recorder never re-audits it to avoid duplicate rows.
 *
 * All methods swallow their own errors: recording must never block a login.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class LoginAttemptRecorder implements LoginAttemptRecorderInterface
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly LockoutManagerInterface $lockoutManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function recordSuccess(int $adminUserId, string $method, ?string $remoteIp = null): void
    {
        try {
            // Clear the failure counter so a successful login is not held against the IP.
            $this->rateLimiter->reset($this->failureKey($remoteIp));
            // Clear durable lockout tracking for this admin/IP on success.
            $this->lockoutManager->registerSuccessfulAttempt($adminUserId, null, $remoteIp);

            if ($method === self::METHOD_PASSWORD) {
                $this->auditLogger->record(
                    AuditLoggerInterface::EVENT_PASSWORD_LOGIN,
                    $this->buildContext($adminUserId, $remoteIp, ['result' => 'success', 'method' => $method])
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey failed to record login success: ' . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function recordFailure(
        ?string $username,
        string $method,
        ?string $reason = null,
        ?string $remoteIp = null
    ): void {
        try {
            // Lockout/rate-limit seam: count every failed attempt.
            $this->rateLimiter->registerAttempt($this->failureKey($remoteIp));
            // Durable lockout evaluation (audits + Fail2Ban + blocking live here).
            $this->lockoutManager->registerFailedAttempt(null, $username, $remoteIp, $method);

            if ($method === self::METHOD_PASSWORD) {
                $metadata = [
                    'result' => 'failure',
                    'method' => $method,
                    'reason' => $reason,
                    'username' => $username,
                ];
                $this->auditLogger->record(
                    AuditLoggerInterface::EVENT_LOGIN_FAILED,
                    $this->buildContext(null, $remoteIp, $metadata, AuditEventInterface::SEVERITY_WARNING)
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey failed to record login failure: ' . $e->getMessage());
        }
    }

    /**
     * Build the audit context array.
     *
     * @param int|null $adminUserId
     * @param string|null $remoteIp
     * @param array<string,mixed> $metadata
     * @param string|null $severity
     * @return array<string,mixed>
     */
    private function buildContext(
        ?int $adminUserId,
        ?string $remoteIp,
        array $metadata,
        ?string $severity = null
    ): array {
        $context = [AuditLoggerInterface::CONTEXT_METADATA => $metadata];
        if ($adminUserId !== null) {
            $context[AuditLoggerInterface::CONTEXT_TARGET] = $adminUserId;
        }
        if ($remoteIp !== null && $remoteIp !== '') {
            $context[AuditLoggerInterface::CONTEXT_IP] = $remoteIp;
        }
        if ($severity !== null) {
            $context[AuditLoggerInterface::CONTEXT_SEVERITY] = $severity;
        }

        return $context;
    }

    /**
     * Rate-limit bucket key for failed attempts from a remote IP.
     *
     * @param string|null $remoteIp
     * @return string
     */
    private function failureKey(?string $remoteIp): string
    {
        return 'login_failure_' . ($remoteIp !== null && $remoteIp !== '' ? $remoteIp : 'unknown');
    }
}
