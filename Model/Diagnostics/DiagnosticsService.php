<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Diagnostics;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterfaceFactory;
use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use FalconMedia\AdminPasskey\Api\DiagnosticsReportRepositoryInterface;
use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Api\TrustedDeviceRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Health\HealthCheckServiceInterface;
use FalconMedia\AdminPasskey\Model\Migration\AdminUserProvider;
use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreServiceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

/**
 * Generates a sanitised support diagnostics ZIP bundle and records/sends reports.
 *
 * The bundle never contains secrets: logs are sanitised, only a whitelist of safe
 * config keys is included, and all paths are constrained to var/adminpasskey.
 */
class DiagnosticsService implements DiagnosticsServiceInterface
{
    /**
     * Directory (relative to var/) where diagnostics bundles are written.
     */
    private const BUNDLE_DIR = 'adminpasskey/diagnostics';

    /**
     * Email identity used as the sender scope.
     */
    private const EMAIL_SENDER = 'general';

    /**
     * Candidate log files (relative to var/) to include, sanitised and filtered.
     *
     * @var string[]
     */
    private const LOG_FILES = ['log/system.log', 'log/exception.log', 'log/debug.log'];

    /**
     * Maximum number of module-relevant log lines kept per file.
     */
    private const LOG_MAX_LINES = 100;

    /**
     * Maximum number of recent audit events summarised in the bundle.
     */
    private const AUDIT_SUMMARY_LIMIT = 50;

    public function __construct(
        private readonly DiagnosticsReportRepositoryInterface $reportRepository,
        private readonly DiagnosticsReportInterfaceFactory $reportFactory,
        private readonly SupportReferenceGenerator $referenceGenerator,
        private readonly ManifestBuilder $manifestBuilder,
        private readonly LogSanitizer $logSanitizer,
        private readonly HealthCheckServiceInterface $healthCheckService,
        private readonly SecurityScoreServiceInterface $securityScoreService,
        private readonly ConfigProvider $configProvider,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly AuditLogInterface $auditLog,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly TrustedDeviceRepositoryInterface $trustedDeviceRepository,
        private readonly LockoutRepositoryInterface $lockoutRepository,
        private readonly AdminUserProvider $adminUserProvider,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly Filesystem $filesystem,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly Json $json,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function generate(?int $actorAdminUserId = null): DiagnosticsReportInterface
    {
        if (!$this->configProvider->isDiagnosticsEnabled()) {
            throw new LocalizedException(__('Diagnostics are disabled in configuration.'));
        }

        $reference = $this->referenceGenerator->generate(
            $this->resolvePrefix(),
            $this->dateTime->gmtDate('Ymd'),
            bin2hex(random_bytes(3))
        );
        if (!$this->referenceGenerator->isValid($reference)) {
            throw new LocalizedException(__('A valid support reference could not be generated.'));
        }

        /** @var DiagnosticsReportInterface $report */
        $report = $this->reportFactory->create();
        $report->setSupportReferenceId($reference);
        $report->setStatus(DiagnosticsReportInterface::STATUS_PENDING);
        $report = $this->reportRepository->save($report);

        try {
            $counts = $this->collectCounts();
            $health = $this->healthCheckService->run()->toArray();
            $score = $this->collectScore();
            $config = $this->collectSafeConfig();
            $auditSummary = $this->collectAuditSummary();
            $logs = $this->collectSanitizedLogs();

            $entries = [
                'manifest.json',
                'versions.json',
                'health.json',
                'security-score.json',
                'config.json',
                'audit-summary.json',
            ];
            foreach (array_keys($logs) as $logName) {
                $entries[] = 'logs/' . $logName;
            }

            $manifest = $this->manifestBuilder->build(
                [
                    'support_reference_id' => $reference,
                    'generated_at' => $this->dateTime->gmtDate(),
                    'versions' => $this->collectVersions(),
                    'counts' => $counts,
                    'health' => $health,
                    'score' => $score,
                    'files' => $entries,
                    'config' => $config,
                ]
            );

            $zipRelPath = $this->writeBundle(
                $reference,
                [
                    'manifest.json' => $this->encode($manifest),
                    'versions.json' => $this->encode($this->collectVersions()),
                    'health.json' => $this->encode($health),
                    'security-score.json' => $this->encode($score),
                    'config.json' => $this->encode($config),
                    'audit-summary.json' => $this->encode($auditSummary),
                ],
                $logs
            );

            $absPath = $this->resolveVarWriter()->getAbsolutePath($zipRelPath);
            $size = is_file($absPath) ? (int) filesize($absPath) : 0;

            $report->setStatus(DiagnosticsReportInterface::STATUS_GENERATED);
            $report->setFiles(
                $this->json->serialize(
                    ['zip_path' => $zipRelPath, 'size' => $size, 'entries' => $entries]
                )
            );
            $report->setCounts($this->json->serialize($counts));
            $report->setMetadata(
                $this->json->serialize(
                    ['health_overall' => $manifest['health']['overall'] ?? 'ok', 'score' => $score['score'] ?? null]
                )
            );
            $report = $this->reportRepository->save($report);

            $this->recordAudit(
                AuditLoggerInterface::EVENT_DIAGNOSTICS_GENERATE,
                $reference,
                $actorAdminUserId,
                ['entries' => count($entries), 'size' => $size]
            );

            return $report;
        } catch (\Throwable $e) {
            $report->setStatus(DiagnosticsReportInterface::STATUS_FAILED);
            $this->reportRepository->save($report);
            $this->logger->error('Failed to generate diagnostics bundle: ' . $e->getMessage(), ['exception' => $e]);

            throw new LocalizedException(__('The diagnostics bundle could not be generated. Please try again.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function send(int $reportId, ?int $actorAdminUserId = null): DiagnosticsReportInterface
    {
        $report = $this->reportRepository->getById($reportId);
        $recipient = $this->configProvider->getDiagnosticsSupportEmail();
        if ($recipient === '') {
            throw new LocalizedException(__('No support email is configured. Set one in the Diagnostics configuration.'));
        }

        try {
            $this->sendEmail($recipient, $report);
            $report->setStatus(DiagnosticsReportInterface::STATUS_SENT);
            $report = $this->reportRepository->save($report);

            $this->recordAudit(
                AuditLoggerInterface::EVENT_DIAGNOSTICS_SEND,
                (string) $report->getSupportReferenceId(),
                $actorAdminUserId,
                ['recipient_domain' => $this->extractEmailDomain($recipient)]
            );

            return $report;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send diagnostics report: ' . $e->getMessage(), ['exception' => $e]);

            throw new LocalizedException(__('The diagnostics report could not be sent. Please try again.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getReportArchivePath(int $reportId): string
    {
        return $this->resolveVarWriter()->getAbsolutePath($this->getReportArchiveRelativePath($reportId));
    }

    /**
     * @inheritdoc
     */
    public function getReportArchiveRelativePath(int $reportId): string
    {
        $report = $this->reportRepository->getById($reportId);
        $files = $report->getFiles();
        if ($files === null || $files === '') {
            throw new LocalizedException(__('This report has no downloadable bundle.'));
        }

        $decoded = $this->json->unserialize($files);
        $zipRelPath = is_array($decoded) ? (string) ($decoded['zip_path'] ?? '') : '';
        if ($zipRelPath === '' || !str_starts_with($zipRelPath, self::BUNDLE_DIR . '/')) {
            throw new LocalizedException(__('The bundle path is invalid.'));
        }

        $writer = $this->resolveVarWriter();
        if (!$writer->isExist($zipRelPath)) {
            throw new LocalizedException(__('The bundle file no longer exists on the server.'));
        }

        return $zipRelPath;
    }

    /**
     * Resolve the configured support reference prefix.
     *
     * @return string
     */
    private function resolvePrefix(): string
    {
        $prefix = $this->configProvider->getSupportReferencePrefix();

        return $prefix !== '' ? $prefix : 'FMAP';
    }

    /**
     * Collect module/Magento/PHP versions.
     *
     * @return array<string, string>
     */
    private function collectVersions(): array
    {
        return [
            'module' => 'FalconMedia_AdminPasskey',
            'magento' => (string) $this->productMetadata->getVersion(),
            'magento_edition' => (string) $this->productMetadata->getEdition(),
            'php' => PHP_VERSION,
        ];
    }

    /**
     * Collect entity counts for the bundle.
     *
     * @return array<string, int>
     */
    private function collectCounts(): array
    {
        return [
            'admins_total' => $this->adminUserProvider->countAdmins(false),
            'admins_active' => $this->adminUserProvider->countAdmins(true),
            'active_credentials' => $this->countByStatus(
                CredentialInterface::STATUS,
                CredentialInterface::STATUS_ACTIVE,
                fn ($sc) => $this->credentialRepository->getList($sc)->getTotalCount()
            ),
            'active_trusted_devices' => $this->countByStatus(
                TrustedDeviceInterface::STATUS,
                TrustedDeviceInterface::STATUS_ACTIVE,
                fn ($sc) => $this->trustedDeviceRepository->getList($sc)->getTotalCount()
            ),
            'active_lockouts' => $this->countByStatus(
                LockoutInterface::STATUS,
                LockoutInterface::STATUS_ACTIVE,
                fn ($sc) => $this->lockoutRepository->getList($sc)->getTotalCount()
            ),
            'audit_events_total' => $this->countAll(fn ($sc) => $this->auditLog->getList($sc)->getTotalCount()),
        ];
    }

    /**
     * Count entities matching a single status filter via the given repository callback.
     *
     * @param string $field
     * @param string $value
     * @param callable(\Magento\Framework\Api\SearchCriteriaInterface): int $counter
     * @return int
     */
    private function countByStatus(string $field, string $value, callable $counter): int
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter($field, $value)->create();

            return $counter($searchCriteria);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Count all entities via the given repository callback.
     *
     * @param callable(\Magento\Framework\Api\SearchCriteriaInterface): int $counter
     * @return int
     */
    private function countAll(callable $counter): int
    {
        try {
            return $counter($this->searchCriteriaBuilder->create());
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Collect the security score, failing safe to an empty structure.
     *
     * @return array<string, mixed>
     */
    private function collectScore(): array
    {
        try {
            $result = $this->securityScoreService->compute();

            return [
                'score' => $result->getScore(),
                'label' => $result->getLabel(),
                'breakdown' => $result->getBreakdown(),
                'recommendations' => $result->getRecommendations(),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Collect the whitelist of safe (non-sensitive) configuration values.
     *
     * @return array<string, mixed>
     */
    private function collectSafeConfig(): array
    {
        return [
            'general_enabled' => $this->configProvider->isEnabled(),
            'passkey_first_login' => $this->configProvider->isPasskeyFirstLogin(),
            'password_fallback_enabled' => $this->configProvider->isPasswordFallbackEnabled(),
            'two_fa_fallback_enabled' => $this->configProvider->isTwoFaFallbackEnabled(),
            'user_verification' => $this->configProvider->getUserVerification(),
            'resident_key' => $this->configProvider->getResidentKey(),
            'ceremony_timeout_ms' => $this->configProvider->getCeremonyTimeoutMs(),
            'challenge_lifetime_seconds' => $this->configProvider->getChallengeLifetimeSeconds(),
            'trusted_devices_enabled' => $this->configProvider->isTrustedDevicesEnabled(),
            'trusted_device_lifetime_days' => $this->configProvider->getTrustedDeviceLifetimeDays(),
            'lockout_enabled' => $this->configProvider->isLockoutEnabled(),
            'max_failed_attempts' => $this->configProvider->getMaxFailedAttempts(),
            'lockout_duration_minutes' => $this->configProvider->getLockoutDurationMinutes(),
            'attempt_window_minutes' => $this->configProvider->getAttemptWindowMinutes(),
            'recovery_enabled' => $this->configProvider->isRecoveryEnabled(),
            'recovery_expiry_minutes' => $this->configProvider->getRecoveryExpiryMinutes(),
            'migration_dashboard_enabled' => $this->configProvider->isMigrationDashboardEnabled(),
            'security_score_enabled' => $this->configProvider->isSecurityScoreEnabled(),
            'weight_authentication' => $this->configProvider->getSecurityScoreWeightAuthentication(),
            'weight_security' => $this->configProvider->getSecurityScoreWeightSecurity(),
            'weight_operational' => $this->configProvider->getSecurityScoreWeightOperational(),
            'weight_threats' => $this->configProvider->getSecurityScoreWeightThreats(),
            'health_check_enabled' => $this->configProvider->isHealthCheckEnabled(),
            'diagnostics_enabled' => $this->configProvider->isDiagnosticsEnabled(),
            'cleanup_enabled' => $this->configProvider->isCleanupEnabled(),
            'challenge_retention_days' => $this->configProvider->getChallengeRetentionDays(),
            'diagnostics_retention_days' => $this->configProvider->getDiagnosticsRetentionDays(),
            'audit_retention_days' => $this->configProvider->getAuditRetentionDays(),
            'score_snapshot_retention_days' => $this->configProvider->getScoreSnapshotRetentionDays(),
            'reminder_retention_days' => $this->configProvider->getReminderRetentionDays(),
            'branding_enabled' => $this->configProvider->isBrandingEnabled(),
            'company_name' => $this->configProvider->getBrandingCompanyName(),
        ];
    }

    /**
     * Collect a non-sensitive audit summary (counts by severity + recent events).
     *
     * @return array<string, mixed>
     */
    private function collectAuditSummary(): array
    {
        try {
            $sortOrder = $this->sortOrderBuilder
                ->setField(AuditEventInterface::ENTITY_ID)
                ->setDirection('DESC')
                ->create();
            $searchCriteria = $this->searchCriteriaBuilder
                ->addSortOrder($sortOrder)
                ->setPageSize(self::AUDIT_SUMMARY_LIMIT)
                ->create();

            $bySeverity = [];
            $recent = [];
            foreach ($this->auditLog->getList($searchCriteria)->getItems() as $event) {
                $severity = (string) $event->getSeverity();
                $bySeverity[$severity] = ($bySeverity[$severity] ?? 0) + 1;
                $recent[] = [
                    'event_type' => (string) $event->getEventType(),
                    'severity' => $severity,
                    'created_at' => (string) $event->getCreatedAt(),
                ];
            }

            return ['by_severity' => $bySeverity, 'recent' => $recent];
        } catch (\Throwable) {
            return ['by_severity' => [], 'recent' => []];
        }
    }

    /**
     * Collect sanitised, module-relevant log excerpts keyed by file name.
     *
     * @return array<string, string>
     */
    private function collectSanitizedLogs(): array
    {
        $logs = [];
        $writer = $this->resolveVarWriter();

        $candidates = self::LOG_FILES;
        $fail2banPath = $this->configProvider->getFail2BanLogPath();
        if ($fail2banPath !== '' && str_starts_with($fail2banPath, 'var/')) {
            $candidates[] = substr($fail2banPath, 4);
        }

        foreach ($candidates as $relPath) {
            try {
                if (!$writer->isExist($relPath) || !$writer->isReadable($relPath)) {
                    continue;
                }
                $content = (string) $writer->readFile($relPath);
                $filtered = $this->filterRelevantLines($content);
                if ($filtered === '') {
                    continue;
                }
                $name = str_replace('/', '_', $relPath);
                $logs[$name] = $this->logSanitizer->sanitize($filtered);
            } catch (\Throwable) {
                continue;
            }
        }

        return $logs;
    }

    /**
     * Keep only the last N module-relevant lines from a log file.
     *
     * @param string $content
     * @return string
     */
    private function filterRelevantLines(string $content): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $relevant = [];
        foreach ($lines as $line) {
            if (stripos($line, 'adminpasskey') !== false || stripos($line, 'AdminPasskey') !== false) {
                $relevant[] = $line;
            }
        }
        if ($relevant === []) {
            return '';
        }
        $relevant = array_slice($relevant, -self::LOG_MAX_LINES);

        return implode("\n", $relevant);
    }

    /**
     * Write the bundle ZIP and return its path relative to var/.
     *
     * @param string $reference
     * @param array<string, string> $files
     * @param array<string, string> $logs
     * @return string
     * @throws LocalizedException
     */
    private function writeBundle(string $reference, array $files, array $logs): string
    {
        $writer = $this->resolveVarWriter();
        $writer->create(self::BUNDLE_DIR);

        $relPath = self::BUNDLE_DIR . '/' . $reference . '.zip';
        $absPath = $writer->getAbsolutePath($relPath);

        $zip = new \ZipArchive();
        if ($zip->open($absPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new LocalizedException(__('The diagnostics archive could not be created.'));
        }

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        foreach ($logs as $name => $content) {
            $zip->addFromString('logs/' . $name, $content);
        }
        $zip->close();

        return $relPath;
    }

    /**
     * Encode a structure as pretty JSON.
     *
     * @param mixed $data
     * @return string
     */
    private function encode(mixed $data): string
    {
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * Send the diagnostics summary email.
     *
     * @param string $recipient
     * @param DiagnosticsReportInterface $report
     * @return void
     */
    private function sendEmail(string $recipient, DiagnosticsReportInterface $report): void
    {
        $counts = $this->decodeCounts($report->getCounts());
        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->resolveTemplateId())
                ->setTemplateOptions(['area' => Area::AREA_ADMINHTML, 'store' => Store::DEFAULT_STORE_ID])
                ->setTemplateVars(
                    [
                        'support_reference_id' => (string) $report->getSupportReferenceId(),
                        'company_name' => $this->resolveCompanyName(),
                        'generated_at' => (string) $report->getCreatedAt(),
                        'summary' => $this->encode($counts),
                    ]
                )
                ->setFromByScope(self::EMAIL_SENDER, Store::DEFAULT_STORE_ID)
                ->addTo($recipient)
                ->getTransport();
            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    /**
     * Decode a report counts JSON string into an array.
     *
     * @param string|null $counts
     * @return array<string, mixed>
     */
    private function decodeCounts(?string $counts): array
    {
        if ($counts === null || $counts === '') {
            return [];
        }
        try {
            $decoded = $this->json->unserialize($counts);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Resolve the configured diagnostics email template identifier.
     *
     * @return string
     */
    private function resolveTemplateId(): string
    {
        $template = $this->configProvider->getDiagnosticsEmailTemplate();

        return $template !== '' ? $template : 'adminpasskey_email_templates_diagnostics_template';
    }

    /**
     * Resolve the branded company name for the email.
     *
     * @return string
     */
    private function resolveCompanyName(): string
    {
        $company = $this->configProvider->getBrandingCompanyName();

        return $company !== '' ? $company : 'FalconMedia';
    }

    /**
     * Extract the domain portion of an email address for non-sensitive audit metadata.
     *
     * @param string $email
     * @return string
     */
    private function extractEmailDomain(string $email): string
    {
        $position = strrpos($email, '@');

        return $position === false ? '' : substr($email, $position + 1);
    }

    /**
     * Resolve the var/ directory writer.
     *
     * @return \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private function resolveVarWriter(): \Magento\Framework\Filesystem\Directory\WriteInterface
    {
        return $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * Record a diagnostics audit event; never break on audit failure.
     *
     * @param string $eventType
     * @param string $reference
     * @param int|null $actorAdminUserId
     * @param array<string, mixed> $metadata
     * @return void
     */
    private function recordAudit(string $eventType, string $reference, ?int $actorAdminUserId, array $metadata): void
    {
        try {
            $context = [
                AuditLoggerInterface::CONTEXT_SUPPORT_REFERENCE_ID => $reference,
                AuditLoggerInterface::CONTEXT_METADATA => $metadata,
            ];
            if ($actorAdminUserId !== null) {
                $context[AuditLoggerInterface::CONTEXT_ACTOR] = $actorAdminUserId;
            }

            $this->auditLogger->record($eventType, $context);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to record audit event for diagnostics action: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
