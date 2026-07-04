<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Dashboard;

/**
 * Immutable snapshot of the raw numbers/states rendered by the dashboard widget.
 *
 * Plain value object; safe to instantiate with `new`. The metrics provider fills
 * it from the module services/repositories; the card assembler turns it into
 * display cards. Keeping this as pure data makes the assembler trivially testable.
 */
class DashboardMetrics
{
    /**
     * @param array{type: string, severity: string, created_at: string}|null $lastSecurityEvent
     */
    public function __construct(
        private readonly int $totalAdmins,
        private readonly int $adminsWithPasskeys,
        private readonly int $passkeyLoginCount,
        private readonly int $passwordLoginCount,
        private readonly int $activeLockouts,
        private readonly int $failedLogins24h,
        private readonly string $healthStatus,
        private readonly int $healthErrors,
        private readonly int $healthWarnings,
        private readonly bool $recoveryActive,
        private readonly ?string $recoveryEnabledAt,
        private readonly int $trustedDevices,
        private readonly int $diagnosticsReports,
        private readonly int $auditEvents,
        private readonly ?string $lastCleanupAt,
        private readonly ?string $lastCleanupStatus,
        private readonly ?array $lastSecurityEvent,
        private readonly ?int $securityScore,
        private readonly ?string $securityScoreLabel
    ) {
    }

    public function getTotalAdmins(): int
    {
        return $this->totalAdmins;
    }

    public function getAdminsWithPasskeys(): int
    {
        return $this->adminsWithPasskeys;
    }

    public function getAdminsWithoutPasskeys(): int
    {
        return max(0, $this->totalAdmins - $this->adminsWithPasskeys);
    }

    /**
     * Adoption percentage (0-100); returns 0 when there are no admins.
     */
    public function getAdoptionPercentage(): int
    {
        if ($this->totalAdmins <= 0) {
            return 0;
        }

        return (int) round(($this->adminsWithPasskeys / $this->totalAdmins) * 100);
    }

    public function getPasskeyLoginCount(): int
    {
        return $this->passkeyLoginCount;
    }

    public function getPasswordLoginCount(): int
    {
        return $this->passwordLoginCount;
    }

    /**
     * Share of passkey logins across all recorded logins (0-100).
     */
    public function getPasskeyLoginPercentage(): int
    {
        $total = $this->passkeyLoginCount + $this->passwordLoginCount;
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($this->passkeyLoginCount / $total) * 100);
    }

    public function getActiveLockouts(): int
    {
        return $this->activeLockouts;
    }

    public function getFailedLogins24h(): int
    {
        return $this->failedLogins24h;
    }

    public function getHealthStatus(): string
    {
        return $this->healthStatus;
    }

    public function getHealthErrors(): int
    {
        return $this->healthErrors;
    }

    public function getHealthWarnings(): int
    {
        return $this->healthWarnings;
    }

    public function isRecoveryActive(): bool
    {
        return $this->recoveryActive;
    }

    public function getRecoveryEnabledAt(): ?string
    {
        return $this->recoveryEnabledAt;
    }

    public function getTrustedDevices(): int
    {
        return $this->trustedDevices;
    }

    public function getDiagnosticsReports(): int
    {
        return $this->diagnosticsReports;
    }

    public function getAuditEvents(): int
    {
        return $this->auditEvents;
    }

    public function getLastCleanupAt(): ?string
    {
        return $this->lastCleanupAt;
    }

    public function getLastCleanupStatus(): ?string
    {
        return $this->lastCleanupStatus;
    }

    /**
     * @return array{type: string, severity: string, created_at: string}|null
     */
    public function getLastSecurityEvent(): ?array
    {
        return $this->lastSecurityEvent;
    }

    public function getSecurityScore(): ?int
    {
        return $this->securityScore;
    }

    public function getSecurityScoreLabel(): ?string
    {
        return $this->securityScoreLabel;
    }
}
