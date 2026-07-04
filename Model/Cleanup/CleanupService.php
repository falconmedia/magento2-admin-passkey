<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Cleanup;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\CleanupLogRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;
use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterfaceFactory;
use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use FalconMedia\AdminPasskey\Api\DiagnosticsReportRepositoryInterface;
use FalconMedia\AdminPasskey\Api\ReminderRepositoryInterface;
use FalconMedia\AdminPasskey\Api\SecurityScoreSnapshotRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Deletes expired module data per configured retention windows and records a
 * {@see CleanupLogInterface} entry (with audit). Cutoff selection is delegated to
 * the pure {@see CleanupTargetSelector}; each category fails independently.
 */
class CleanupService implements CleanupServiceInterface
{
    /**
     * Directory (relative to var/) that holds diagnostics bundles.
     */
    private const BUNDLE_DIR = 'adminpasskey/diagnostics';

    public function __construct(
        private readonly CleanupTargetSelector $targetSelector,
        private readonly ConfigProvider $configProvider,
        private readonly ChallengeRepositoryInterface $challengeRepository,
        private readonly AuditLogInterface $auditLog,
        private readonly SecurityScoreSnapshotRepositoryInterface $scoreSnapshotRepository,
        private readonly ReminderRepositoryInterface $reminderRepository,
        private readonly DiagnosticsReportRepositoryInterface $diagnosticsReportRepository,
        private readonly CleanupLogRepositoryInterface $cleanupLogRepository,
        private readonly CleanupLogInterfaceFactory $cleanupLogFactory,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly Filesystem $filesystem,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Json $json,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function run(?int $actorAdminUserId = null): CleanupLogInterface
    {
        $now = $this->dateTime->gmtDate('Y-m-d H:i:s');
        $cutoffs = $this->targetSelector->selectCutoffs($now, $this->resolveRetentionDays());

        $counts = [];
        $failures = [];
        foreach ($cutoffs as $category => $cutoff) {
            if ($cutoff === null) {
                continue;
            }
            try {
                $counts[$category] = $this->cleanupCategory($category, $cutoff);
            } catch (\Throwable $e) {
                $failures[$category] = $e->getMessage();
                $this->logger->error(
                    sprintf('AdminPasskey cleanup failed for "%s": %s', $category, $e->getMessage()),
                    ['exception' => $e]
                );
            }
        }

        $status = $failures === [] ? CleanupLogInterface::STATUS_SUCCESS : CleanupLogInterface::STATUS_FAILED;
        $log = $this->recordLog(array_keys($counts), $counts, $status, $failures);
        $this->recordAudit($counts, $status, $actorAdminUserId);

        return $log;
    }

    /**
     * Resolve the retention window (days) per category from configuration.
     *
     * @return array<string, int>
     */
    private function resolveRetentionDays(): array
    {
        return [
            CleanupTargetSelector::CATEGORY_CHALLENGES => $this->configProvider->getChallengeRetentionDays(),
            CleanupTargetSelector::CATEGORY_DIAGNOSTICS => $this->configProvider->getDiagnosticsRetentionDays(),
            CleanupTargetSelector::CATEGORY_AUDIT => $this->configProvider->getAuditRetentionDays(),
            CleanupTargetSelector::CATEGORY_SCORE_SNAPSHOTS => $this->configProvider->getScoreSnapshotRetentionDays(),
            CleanupTargetSelector::CATEGORY_REMINDERS => $this->configProvider->getReminderRetentionDays(),
        ];
    }

    /**
     * Clean up a single category and return the number of removed rows.
     *
     * @param string $category
     * @param string $cutoff
     * @return int
     */
    private function cleanupCategory(string $category, string $cutoff): int
    {
        return match ($category) {
            CleanupTargetSelector::CATEGORY_CHALLENGES => $this->challengeRepository->deleteExpired($cutoff),
            CleanupTargetSelector::CATEGORY_AUDIT => $this->auditLog->deleteOlderThan($cutoff),
            CleanupTargetSelector::CATEGORY_SCORE_SNAPSHOTS => $this->scoreSnapshotRepository->deleteOlderThan($cutoff),
            CleanupTargetSelector::CATEGORY_REMINDERS => $this->reminderRepository->deleteOlderThan($cutoff),
            CleanupTargetSelector::CATEGORY_DIAGNOSTICS => $this->cleanupDiagnostics($cutoff),
            default => 0,
        };
    }

    /**
     * Delete diagnostics reports older than the cutoff together with their bundles.
     *
     * @param string $cutoff
     * @return int
     */
    private function cleanupDiagnostics(string $cutoff): int
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(DiagnosticsReportInterface::CREATED_AT, $cutoff, 'lt')
            ->create();

        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $deleted = 0;
        foreach ($this->diagnosticsReportRepository->getList($searchCriteria)->getItems() as $report) {
            $id = (int) $report->getId();
            if ($id <= 0) {
                continue;
            }
            $this->deleteBundleFile($writer, $report->getFiles());
            $this->diagnosticsReportRepository->deleteById($id);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * Delete a diagnostics bundle file if it lives under the module's bundle directory.
     *
     * @param \Magento\Framework\Filesystem\Directory\WriteInterface $writer
     * @param string|null $filesJson
     * @return void
     */
    private function deleteBundleFile(
        \Magento\Framework\Filesystem\Directory\WriteInterface $writer,
        ?string $filesJson
    ): void {
        if ($filesJson === null || $filesJson === '') {
            return;
        }
        try {
            $decoded = $this->json->unserialize($filesJson);
            $zipRelPath = is_array($decoded) ? (string) ($decoded['zip_path'] ?? '') : '';
            if ($zipRelPath !== ''
                && str_starts_with($zipRelPath, self::BUNDLE_DIR . '/')
                && $writer->isExist($zipRelPath)
            ) {
                $writer->delete($zipRelPath);
            }
        } catch (\Throwable $e) {
            $this->logger->warning(
                'AdminPasskey cleanup could not delete diagnostics bundle: ' . $e->getMessage()
            );
        }
    }

    /**
     * Persist a cleanup log entry.
     *
     * @param string[] $categories
     * @param array<string, int> $counts
     * @param string $status
     * @param array<string, string> $failures
     * @return CleanupLogInterface
     */
    private function recordLog(array $categories, array $counts, string $status, array $failures): CleanupLogInterface
    {
        /** @var CleanupLogInterface $log */
        $log = $this->cleanupLogFactory->create();
        $log->setCategories($this->json->serialize($categories));
        $log->setCounts($this->json->serialize($counts));
        $log->setStatus($status);
        if ($failures !== []) {
            $log->setMetadata($this->json->serialize(['failures' => $failures]));
        }

        return $this->cleanupLogRepository->save($log);
    }

    /**
     * Record a cleanup audit event; never break on audit failure.
     *
     * @param array<string, int> $counts
     * @param string $status
     * @param int|null $actorAdminUserId
     * @return void
     */
    private function recordAudit(array $counts, string $status, ?int $actorAdminUserId): void
    {
        try {
            $context = [
                AuditLoggerInterface::CONTEXT_METADATA => ['counts' => $counts, 'status' => $status],
            ];
            if ($actorAdminUserId !== null) {
                $context[AuditLoggerInterface::CONTEXT_ACTOR] = $actorAdminUserId;
            }

            $this->auditLogger->record(AuditLoggerInterface::EVENT_CLEANUP, $context);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to record audit event for cleanup: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
