<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Dashboard;

use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Health\HealthCheckResult;
use Magento\Framework\AuthorizationInterface;

/**
 * Turns raw {@see DashboardMetrics} into display-ready {@see DashboardCard}s,
 * honouring both the per-card system-config toggles and the current admin's ACL.
 *
 * All data comes from the injected metrics provider (mockable in tests); this
 * class contains no persistence or framework rendering, only presentation logic.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @internal Admin-only support; not part of a public web API contract.
 */
class DashboardCardAssembler
{
    /**
     * ACL resource required to view each card.
     *
     * @var array<string, string>
     */
    private const CARD_ACL = [
        'passkey_adoption' => 'FalconMedia_AdminPasskey::migration',
        'admins_without_passkeys' => 'FalconMedia_AdminPasskey::migration',
        'login_ratio' => 'FalconMedia_AdminPasskey::audit_log',
        'active_lockouts' => 'FalconMedia_AdminPasskey::lockouts',
        'failed_logins_24h' => 'FalconMedia_AdminPasskey::audit_log',
        'health_status' => 'FalconMedia_AdminPasskey::health',
        'recovery_status' => 'FalconMedia_AdminPasskey::recovery',
        'trusted_devices' => 'FalconMedia_AdminPasskey::trusted_devices',
        'diagnostics_reports' => 'FalconMedia_AdminPasskey::diagnostics',
        'audit_events' => 'FalconMedia_AdminPasskey::audit_log',
        'last_cleanup' => 'FalconMedia_AdminPasskey::diagnostics',
        'last_security_event' => 'FalconMedia_AdminPasskey::audit_log',
        'security_score' => 'FalconMedia_AdminPasskey::security_score',
        'quick_actions' => 'FalconMedia_AdminPasskey::admin_passkey',
    ];

    public function __construct(
        private readonly DashboardMetricsProviderInterface $metricsProvider,
        private readonly ConfigProvider $configProvider,
        private readonly AuthorizationInterface $authorization
    ) {
    }

    /**
     * Build the ordered list of cards the current admin may see.
     *
     * @return DashboardCard[]
     */
    public function assemble(): array
    {
        $metrics = $this->metricsProvider->getMetrics();
        $cards = [];

        foreach (array_keys(self::CARD_ACL) as $cardId) {
            if (!$this->isCardVisible($cardId)) {
                continue;
            }
            $card = $this->buildCard($cardId, $metrics);
            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * Whether a card is both enabled in config and permitted by ACL.
     */
    private function isCardVisible(string $cardId): bool
    {
        if (!$this->configProvider->isDashboardCardEnabled($cardId)) {
            return false;
        }

        return $this->authorization->isAllowed(self::CARD_ACL[$cardId]);
    }

    /**
     * Dispatch to the builder for a single card.
     */
    private function buildCard(string $cardId, DashboardMetrics $metrics): ?DashboardCard
    {
        return match ($cardId) {
            'passkey_adoption' => $this->buildAdoption($metrics),
            'admins_without_passkeys' => $this->buildAdminsWithout($metrics),
            'login_ratio' => $this->buildLoginRatio($metrics),
            'active_lockouts' => $this->buildActiveLockouts($metrics),
            'failed_logins_24h' => $this->buildFailedLogins($metrics),
            'health_status' => $this->buildHealth($metrics),
            'recovery_status' => $this->buildRecovery($metrics),
            'trusted_devices' => $this->buildTrustedDevices($metrics),
            'diagnostics_reports' => $this->buildDiagnostics($metrics),
            'audit_events' => $this->buildAuditEvents($metrics),
            'last_cleanup' => $this->buildLastCleanup($metrics),
            'last_security_event' => $this->buildLastSecurityEvent($metrics),
            'security_score' => $this->buildSecurityScore($metrics),
            'quick_actions' => $this->buildQuickActions(),
            default => null,
        };
    }

    private function buildAdoption(DashboardMetrics $metrics): DashboardCard
    {
        $percent = $metrics->getAdoptionPercentage();
        $status = $percent >= 80
            ? DashboardCard::STATUS_OK
            : ($percent >= 40 ? DashboardCard::STATUS_WARNING : DashboardCard::STATUS_CRITICAL);

        return new DashboardCard(
            'passkey_adoption',
            (string) __('Passkey Adoption'),
            $status,
            $percent . '%',
            (string) __('%1 of %2 admins have a passkey.', $metrics->getAdminsWithPasskeys(), $metrics->getTotalAdmins()),
            [$this->link((string) __('Migration Dashboard'), 'adminpasskey/migration/index')]
        );
    }

    private function buildAdminsWithout(DashboardMetrics $metrics): DashboardCard
    {
        $count = $metrics->getAdminsWithoutPasskeys();

        return new DashboardCard(
            'admins_without_passkeys',
            (string) __('Admins Without Passkeys'),
            $count === 0 ? DashboardCard::STATUS_OK : DashboardCard::STATUS_WARNING,
            (string) $count,
            (string) __('Administrators that still rely on a password.'),
            [$this->link((string) __('Migration Dashboard'), 'adminpasskey/migration/index')]
        );
    }

    private function buildLoginRatio(DashboardMetrics $metrics): DashboardCard
    {
        $percent = $metrics->getPasskeyLoginPercentage();

        return new DashboardCard(
            'login_ratio',
            (string) __('Passkey vs Password Logins'),
            $percent >= 50 ? DashboardCard::STATUS_OK : DashboardCard::STATUS_INFO,
            $percent . '%',
            (string) __(
                'Passkey logins: %1, password logins: %2.',
                $metrics->getPasskeyLoginCount(),
                $metrics->getPasswordLoginCount()
            )
        );
    }

    private function buildActiveLockouts(DashboardMetrics $metrics): DashboardCard
    {
        $count = $metrics->getActiveLockouts();

        return new DashboardCard(
            'active_lockouts',
            (string) __('Active Lockouts'),
            $count > 0 ? DashboardCard::STATUS_CRITICAL : DashboardCard::STATUS_OK,
            (string) $count,
            (string) __('Accounts currently locked after failed attempts.'),
            [$this->link((string) __('Lockouts'), 'adminpasskey/lockout/index')]
        );
    }

    private function buildFailedLogins(DashboardMetrics $metrics): DashboardCard
    {
        $count = $metrics->getFailedLogins24h();

        return new DashboardCard(
            'failed_logins_24h',
            (string) __('Failed Logins (24h)'),
            $count > 0 ? DashboardCard::STATUS_WARNING : DashboardCard::STATUS_OK,
            (string) $count,
            (string) __('Failed admin login attempts in the last 24 hours.'),
            [$this->link((string) __('Audit Log'), 'adminpasskey/audit/index')]
        );
    }

    private function buildHealth(DashboardMetrics $metrics): DashboardCard
    {
        $status = match ($metrics->getHealthStatus()) {
            HealthCheckResult::STATUS_OK => DashboardCard::STATUS_OK,
            HealthCheckResult::STATUS_WARNING => DashboardCard::STATUS_WARNING,
            HealthCheckResult::STATUS_ERROR => DashboardCard::STATUS_CRITICAL,
            default => DashboardCard::STATUS_NEUTRAL,
        };

        return new DashboardCard(
            'health_status',
            (string) __('Health Status'),
            $status,
            ucfirst($metrics->getHealthStatus()),
            (string) __('%1 error(s), %2 warning(s).', $metrics->getHealthErrors(), $metrics->getHealthWarnings()),
            [$this->link((string) __('Health Check'), 'adminpasskey/health/index')]
        );
    }

    private function buildRecovery(DashboardMetrics $metrics): DashboardCard
    {
        $active = $metrics->isRecoveryActive();

        return new DashboardCard(
            'recovery_status',
            (string) __('Recovery Mode'),
            $active ? DashboardCard::STATUS_WARNING : DashboardCard::STATUS_OK,
            $active ? (string) __('Enabled') : (string) __('Disabled'),
            $active && $metrics->getRecoveryEnabledAt() !== null
                ? (string) __('Enabled at %1.', $metrics->getRecoveryEnabledAt())
                : (string) __('Emergency recovery is not active.'),
            [$this->link((string) __('Recovery'), 'adminpasskey/recovery/index')]
        );
    }

    private function buildTrustedDevices(DashboardMetrics $metrics): DashboardCard
    {
        return new DashboardCard(
            'trusted_devices',
            (string) __('Trusted Devices'),
            DashboardCard::STATUS_INFO,
            (string) $metrics->getTrustedDevices(),
            (string) __('Active trusted browsers across all admins.'),
            [$this->link((string) __('Trusted Devices'), 'adminpasskey/trusteddevice/index')]
        );
    }

    private function buildDiagnostics(DashboardMetrics $metrics): DashboardCard
    {
        return new DashboardCard(
            'diagnostics_reports',
            (string) __('Diagnostics Reports'),
            DashboardCard::STATUS_INFO,
            (string) $metrics->getDiagnosticsReports(),
            (string) __('Support diagnostics bundles generated.'),
            [$this->link((string) __('Diagnostics'), 'adminpasskey/diagnostics/index')]
        );
    }

    private function buildAuditEvents(DashboardMetrics $metrics): DashboardCard
    {
        return new DashboardCard(
            'audit_events',
            (string) __('Audit Events'),
            DashboardCard::STATUS_INFO,
            (string) $metrics->getAuditEvents(),
            (string) __('Total recorded security audit events.'),
            [$this->link((string) __('Audit Log'), 'adminpasskey/audit/index')]
        );
    }

    private function buildLastCleanup(DashboardMetrics $metrics): DashboardCard
    {
        $at = $metrics->getLastCleanupAt();
        $status = $metrics->getLastCleanupStatus() === 'failed'
            ? DashboardCard::STATUS_CRITICAL
            : ($at === null ? DashboardCard::STATUS_NEUTRAL : DashboardCard::STATUS_OK);

        return new DashboardCard(
            'last_cleanup',
            (string) __('Last Cleanup'),
            $status,
            $at ?? (string) __('Never'),
            $metrics->getLastCleanupStatus() !== null
                ? (string) __('Status: %1.', $metrics->getLastCleanupStatus())
                : (string) __('No cleanup run recorded yet.'),
            [$this->link((string) __('Data Cleanup'), 'adminpasskey/cleanup/index')]
        );
    }

    private function buildLastSecurityEvent(DashboardMetrics $metrics): DashboardCard
    {
        $event = $metrics->getLastSecurityEvent();
        if ($event === null) {
            return new DashboardCard(
                'last_security_event',
                (string) __('Last Security Event'),
                DashboardCard::STATUS_NEUTRAL,
                (string) __('None'),
                (string) __('No security events recorded yet.'),
                [$this->link((string) __('Audit Log'), 'adminpasskey/audit/index')]
            );
        }

        return new DashboardCard(
            'last_security_event',
            (string) __('Last Security Event'),
            $this->severityToStatus($event['severity']),
            $event['type'],
            (string) __('%1 at %2.', ucfirst($event['severity']), $event['created_at']),
            [$this->link((string) __('Audit Log'), 'adminpasskey/audit/index')]
        );
    }

    private function buildSecurityScore(DashboardMetrics $metrics): DashboardCard
    {
        $score = $metrics->getSecurityScore();
        $status = $score === null
            ? DashboardCard::STATUS_NEUTRAL
            : ($score >= 80
                ? DashboardCard::STATUS_OK
                : ($score >= 50 ? DashboardCard::STATUS_WARNING : DashboardCard::STATUS_CRITICAL));

        return new DashboardCard(
            'security_score',
            (string) __('Security Score'),
            $status,
            $score !== null ? (string) $score : '—',
            $metrics->getSecurityScoreLabel() !== null
                ? (string) __('Rating: %1.', $metrics->getSecurityScoreLabel())
                : (string) __('No score computed yet.'),
            [$this->link((string) __('Security Score'), 'adminpasskey/securityscore/index')]
        );
    }

    private function buildQuickActions(): DashboardCard
    {
        return new DashboardCard(
            'quick_actions',
            (string) __('Quick Actions'),
            DashboardCard::STATUS_NEUTRAL,
            null,
            (string) __('Jump to the Admin Passkey management screens.'),
            [
                $this->link((string) __('Migration Dashboard'), 'adminpasskey/migration/index'),
                $this->link((string) __('Security Score'), 'adminpasskey/securityscore/index'),
                $this->link((string) __('Health Check'), 'adminpasskey/health/index'),
                $this->link((string) __('Lockouts'), 'adminpasskey/lockout/index'),
                $this->link((string) __('Trusted Devices'), 'adminpasskey/trusteddevice/index'),
                $this->link((string) __('Audit Log'), 'adminpasskey/audit/index'),
                $this->link((string) __('Diagnostics'), 'adminpasskey/diagnostics/index'),
                $this->link((string) __('Recovery'), 'adminpasskey/recovery/index'),
            ]
        );
    }

    /**
     * Map an audit severity to a card status.
     */
    private function severityToStatus(string $severity): string
    {
        return match ($severity) {
            AuditEventInterface::SEVERITY_CRITICAL => DashboardCard::STATUS_CRITICAL,
            AuditEventInterface::SEVERITY_WARNING => DashboardCard::STATUS_WARNING,
            AuditEventInterface::SEVERITY_NOTICE => DashboardCard::STATUS_INFO,
            default => DashboardCard::STATUS_INFO,
        };
    }

    /**
     * Build a single action-link descriptor.
     *
     * @return array{label: string, route: string}
     */
    private function link(string $label, string $route): array
    {
        return ['label' => $label, 'route' => $route];
    }
}
