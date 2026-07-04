<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Dashboard;

use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\CleanupLogRepositoryInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use FalconMedia\AdminPasskey\Api\DiagnosticsReportRepositoryInterface;
use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Api\TrustedDeviceRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Health\HealthCheckServiceInterface;
use FalconMedia\AdminPasskey\Model\Migration\AdminUserProvider;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreServiceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;

/**
 * Reads every dashboard metric from the existing module services and packages
 * them into an immutable {@see DashboardMetrics} value object.
 *
 * Every data source is read defensively; a failing source contributes a safe
 * zero/empty value rather than breaking the whole dashboard.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @internal Admin-only support; not part of a public web API contract.
 */
class DashboardMetricsProvider implements DashboardMetricsProviderInterface
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly AdminUserProvider $adminUserProvider,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly AuditLogInterface $auditLog,
        private readonly LockoutRepositoryInterface $lockoutRepository,
        private readonly TrustedDeviceRepositoryInterface $trustedDeviceRepository,
        private readonly DiagnosticsReportRepositoryInterface $diagnosticsReportRepository,
        private readonly CleanupLogRepositoryInterface $cleanupLogRepository,
        private readonly HealthCheckServiceInterface $healthCheckService,
        private readonly RecoveryModeServiceInterface $recoveryModeService,
        private readonly SecurityScoreServiceInterface $securityScoreService,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getMetrics(): DashboardMetrics
    {
        $totalAdmins = $this->safeInt(fn (): int => $this->adminUserProvider->countAdmins(true));
        [$health, $healthErrors, $healthWarnings] = $this->resolveHealth();
        [$score, $scoreLabel] = $this->resolveSecurityScore();
        [$lastCleanupAt, $lastCleanupStatus] = $this->resolveLastCleanup();
        $recovery = $this->safeBool(fn (): bool => $this->recoveryModeService->isActive());

        return new DashboardMetrics(
            $totalAdmins,
            $this->countAdminsWithPasskeys(),
            $this->countAuditByType(AuditLoggerInterface::EVENT_PASSKEY_LOGIN),
            $this->countAuditByType(AuditLoggerInterface::EVENT_PASSWORD_LOGIN),
            $this->countActiveLockouts(),
            $this->countFailedLogins24h(),
            $health,
            $healthErrors,
            $healthWarnings,
            $recovery,
            $this->resolveRecoveryEnabledAt(),
            $this->countActiveTrustedDevices(),
            $this->countDiagnosticsReports(),
            $this->countAuditEvents(),
            $lastCleanupAt,
            $lastCleanupStatus,
            $this->resolveLastSecurityEvent(),
            $score,
            $scoreLabel
        );
    }

    /**
     * Count active admins that own at least one active passkey.
     */
    private function countAdminsWithPasskeys(): int
    {
        try {
            $count = 0;
            foreach ($this->adminUserProvider->getAdminIds(true) as $adminId) {
                if ($this->credentialRepository->listActiveForAdmin($adminId)->getTotalCount() > 0) {
                    $count++;
                }
            }

            return $count;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Count audit events of a given type.
     */
    private function countAuditByType(string $eventType): int
    {
        try {
            $this->searchCriteriaBuilder->addFilter(AuditEventInterface::EVENT_TYPE, $eventType);

            return $this->auditLog->getList($this->searchCriteriaBuilder->create())->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Count failed login audit events in the trailing 24 hours.
     */
    private function countFailedLogins24h(): int
    {
        try {
            $since = gmdate('Y-m-d H:i:s', time() - 86400);
            $this->searchCriteriaBuilder->addFilter(
                AuditEventInterface::EVENT_TYPE,
                AuditLoggerInterface::EVENT_LOGIN_FAILED
            );
            $this->searchCriteriaBuilder->addFilter(AuditEventInterface::CREATED_AT, $since, 'gteq');

            return $this->auditLog->getList($this->searchCriteriaBuilder->create())->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Total number of audit events.
     */
    private function countAuditEvents(): int
    {
        try {
            return $this->auditLog->getList($this->searchCriteriaBuilder->create())->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Number of currently active lockouts.
     */
    private function countActiveLockouts(): int
    {
        try {
            $this->searchCriteriaBuilder->addFilter(LockoutInterface::STATUS, LockoutInterface::STATUS_ACTIVE);

            return $this->lockoutRepository->getList($this->searchCriteriaBuilder->create())->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Number of currently active trusted devices.
     */
    private function countActiveTrustedDevices(): int
    {
        try {
            $this->searchCriteriaBuilder->addFilter(
                TrustedDeviceInterface::STATUS,
                TrustedDeviceInterface::STATUS_ACTIVE
            );

            return $this->trustedDeviceRepository->getList($this->searchCriteriaBuilder->create())->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Total number of diagnostics reports.
     */
    private function countDiagnosticsReports(): int
    {
        try {
            return $this->diagnosticsReportRepository
                ->getList($this->searchCriteriaBuilder->create())
                ->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Resolve overall health status plus error/warning counts.
     *
     * @return array{0: string, 1: int, 2: int}
     */
    private function resolveHealth(): array
    {
        try {
            $report = $this->healthCheckService->run();

            return [$report->getOverallStatus(), $report->getErrorCount(), $report->getWarningCount()];
        } catch (\Throwable) {
            return ['unknown', 0, 0];
        }
    }

    /**
     * Resolve the timestamp recovery mode was last enabled, when active.
     */
    private function resolveRecoveryEnabledAt(): ?string
    {
        try {
            $current = $this->recoveryModeService->getCurrent();

            return $current?->getEnabledAt();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the last cleanup timestamp and status.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveLastCleanup(): array
    {
        try {
            $latest = $this->cleanupLogRepository->getLatest();
            if ($latest === null) {
                return [null, null];
            }

            return [$latest->getCreatedAt(), $latest->getStatus()];
        } catch (\Throwable) {
            return [null, null];
        }
    }

    /**
     * Resolve the most recent security audit event.
     *
     * @return array{type: string, severity: string, created_at: string}|null
     */
    private function resolveLastSecurityEvent(): ?array
    {
        try {
            $sortOrder = $this->sortOrderBuilder
                ->setField(AuditEventInterface::CREATED_AT)
                ->setDirection(SortOrder::SORT_DESC)
                ->create();
            $criteria = $this->searchCriteriaBuilder
                ->addSortOrder($sortOrder)
                ->setPageSize(1)
                ->setCurrentPage(1)
                ->create();

            $items = $this->auditLog->getList($criteria)->getItems();
            $event = reset($items);
            if (!$event instanceof AuditEventInterface) {
                return null;
            }

            return [
                'type' => (string) $event->getEventType(),
                'severity' => (string) $event->getSeverity(),
                'created_at' => (string) $event->getCreatedAt(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve the current security score and label.
     *
     * @return array{0: int|null, 1: string|null}
     */
    private function resolveSecurityScore(): array
    {
        try {
            $snapshot = $this->securityScoreService->getCurrent();
            if ($snapshot !== null) {
                return [$snapshot->getScore(), $snapshot->getLabel()];
            }

            $result = $this->securityScoreService->compute();

            return [$result->getScore(), $result->getLabel()];
        } catch (\Throwable) {
            return [null, null];
        }
    }

    /**
     * Run an int-returning callback, defaulting to 0 on failure.
     *
     * @param callable(): int $callback
     */
    private function safeInt(callable $callback): int
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Run a bool-returning callback, defaulting to false on failure.
     *
     * @param callable(): bool $callback
     */
    private function safeBool(callable $callback): bool
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return false;
        }
    }
}
