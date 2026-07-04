<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SecurityScore;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotInterface;
use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotInterfaceFactory;
use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Api\SecurityScoreSnapshotRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Health\HealthCheckServiceInterface;
use FalconMedia\AdminPasskey\Model\Migration\AdminUserProvider;
use FalconMedia\AdminPasskey\Model\Migration\TwoFactorAuthStatusProvider;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\RelyingPartyProvider;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Builds the raw signals, delegates the maths to {@see SecurityScoreCalculator}
 * and turns recommendation codes into translated messages. Snapshots are audited.
 */
class SecurityScoreService implements SecurityScoreServiceInterface
{
    /**
     * Seconds in the trailing failed-login window (24 hours).
     */
    private const FAILED_LOGIN_WINDOW_SECONDS = 86400;

    public function __construct(
        private readonly SecurityScoreCalculator $calculator,
        private readonly HealthCheckServiceInterface $healthCheckService,
        private readonly ConfigProvider $configProvider,
        private readonly AdminUserProvider $adminUserProvider,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly LockoutRepositoryInterface $lockoutRepository,
        private readonly AuditLogInterface $auditLog,
        private readonly RelyingPartyProvider $relyingPartyProvider,
        private readonly TwoFactorAuthStatusProvider $twoFactorAuthStatusProvider,
        private readonly RecoveryModeServiceInterface $recoveryModeService,
        private readonly SecurityScoreSnapshotRepositoryInterface $snapshotRepository,
        private readonly SecurityScoreSnapshotInterfaceFactory $snapshotFactory,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly Json $json,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function compute(): SecurityScoreResult
    {
        $signals = $this->buildSignals();
        $breakdown = $this->calculator->categoryScores($signals);
        $weights = [
            SecurityScoreCalculator::CATEGORY_AUTHENTICATION => $this->configProvider->getSecurityScoreWeightAuthentication(),
            SecurityScoreCalculator::CATEGORY_SECURITY => $this->configProvider->getSecurityScoreWeightSecurity(),
            SecurityScoreCalculator::CATEGORY_OPERATIONAL => $this->configProvider->getSecurityScoreWeightOperational(),
            SecurityScoreCalculator::CATEGORY_THREATS => $this->configProvider->getSecurityScoreWeightThreats(),
        ];

        $score = $this->calculator->calculate($breakdown, $weights);
        $label = $this->calculator->label($score);
        $recommendations = $this->resolveRecommendations($this->calculator->recommendations($signals));

        return new SecurityScoreResult($score, $label, $breakdown, $recommendations);
    }

    /**
     * @inheritdoc
     */
    public function snapshot(): SecurityScoreSnapshotInterface
    {
        $result = $this->compute();

        /** @var SecurityScoreSnapshotInterface $snapshot */
        $snapshot = $this->snapshotFactory->create();
        $snapshot->setScore($result->getScore());
        $snapshot->setLabel($result->getLabel());
        $snapshot->setCategoryBreakdown($this->json->serialize($result->getBreakdown()));
        $snapshot->setRecommendations($this->json->serialize($result->getRecommendations()));

        $saved = $this->snapshotRepository->save($snapshot);
        $this->recordSnapshotAudit($saved);

        return $saved;
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?SecurityScoreSnapshotInterface
    {
        return $this->snapshotRepository->getCurrent();
    }

    /**
     * @inheritdoc
     */
    public function getHistory(int $limit = 20): array
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(SecurityScoreSnapshotInterface::ENTITY_ID)
            ->setDirection('DESC')
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addSortOrder($sortOrder)
            ->setPageSize(max(1, $limit))
            ->create();

        return $this->snapshotRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Gather the raw signals used by the calculator.
     *
     * @return SecurityScoreSignals
     */
    private function buildSignals(): SecurityScoreSignals
    {
        $totalAdmins = $this->adminUserProvider->countAdmins(true);
        $adminsWithPasskey = $this->countAdminsWithPasskey($this->adminUserProvider->getAdminIds(true));
        $health = $this->healthCheckService->run();

        return new SecurityScoreSignals(
            $totalAdmins,
            $adminsWithPasskey,
            $this->configProvider->isPasskeyFirstLogin(),
            $this->configProvider->isPasswordFallbackEnabled(),
            $this->configProvider->isLockoutEnabled(),
            $this->configProvider->isTrustedDevicesEnabled(),
            $this->isRecoveryActive(),
            str_starts_with($this->relyingPartyProvider->getOrigin(), 'https://'),
            $this->twoFactorAuthStatusProvider->isEnabled(),
            $this->countActiveLockouts(),
            $this->countRecentFailedLogins(),
            $health->getErrorCount(),
            $health->getWarningCount(),
            $this->configProvider->isCleanupEnabled(),
            $this->configProvider->isDiagnosticsEnabled()
        );
    }

    /**
     * Count admins that have at least one active passkey.
     *
     * @param int[] $adminIds
     * @return int
     */
    private function countAdminsWithPasskey(array $adminIds): int
    {
        $count = 0;
        foreach ($adminIds as $adminId) {
            try {
                if ($this->credentialRepository->listActiveForAdmin($adminId)->getTotalCount() > 0) {
                    $count++;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $count;
    }

    /**
     * Whether recovery mode is active, failing safe to false.
     *
     * @return bool
     */
    private function isRecoveryActive(): bool
    {
        try {
            return $this->recoveryModeService->isActive();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Count currently active lockouts.
     *
     * @return int
     */
    private function countActiveLockouts(): int
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(LockoutInterface::STATUS, LockoutInterface::STATUS_ACTIVE)
                ->create();

            return $this->lockoutRepository->getList($searchCriteria)->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Count failed logins in the trailing 24 hours.
     *
     * @return int
     */
    private function countRecentFailedLogins(): int
    {
        try {
            $cutoff = $this->dateTime->gmtDate(
                'Y-m-d H:i:s',
                $this->dateTime->gmtTimestamp() - self::FAILED_LOGIN_WINDOW_SECONDS
            );

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(AuditEventInterface::EVENT_TYPE, AuditLoggerInterface::EVENT_LOGIN_FAILED)
                ->addFilter(AuditEventInterface::CREATED_AT, $cutoff, 'gteq')
                ->create();

            return $this->auditLog->getList($searchCriteria)->getTotalCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Map recommendation codes to translated messages.
     *
     * @param string[] $codes
     * @return array<int, array{code: string, message: string}>
     */
    private function resolveRecommendations(array $codes): array
    {
        $messages = [
            SecurityScoreCalculator::RECOMMENDATION_INCREASE_ADOPTION =>
                (string) __('Encourage more administrators to register a passkey.'),
            SecurityScoreCalculator::RECOMMENDATION_ENABLE_PASSKEY_FIRST =>
                (string) __('Enable passkey-first login so passkeys are offered before passwords.'),
            SecurityScoreCalculator::RECOMMENDATION_DISABLE_PASSWORD_FALLBACK =>
                (string) __('Consider disabling password fallback once every admin has a passkey.'),
            SecurityScoreCalculator::RECOMMENDATION_ENABLE_HTTPS =>
                (string) __('Serve the Admin over HTTPS; WebAuthn requires a secure context.'),
            SecurityScoreCalculator::RECOMMENDATION_ENABLE_LOCKOUT =>
                (string) __('Enable lockout protection to throttle brute-force attempts.'),
            SecurityScoreCalculator::RECOMMENDATION_ENABLE_CLEANUP =>
                (string) __('Enable scheduled cleanup so expired data does not accumulate.'),
            SecurityScoreCalculator::RECOMMENDATION_RESOLVE_HEALTH =>
                (string) __('Resolve the failing health checks to improve your score.'),
            SecurityScoreCalculator::RECOMMENDATION_DISABLE_RECOVERY =>
                (string) __('Disable emergency recovery mode now that access is restored.'),
            SecurityScoreCalculator::RECOMMENDATION_REVIEW_LOCKOUTS =>
                (string) __('Review the active lockouts for signs of an attack.'),
        ];

        $resolved = [];
        foreach ($codes as $code) {
            $resolved[] = [
                'code' => $code,
                'message' => $messages[$code] ?? $code,
            ];
        }

        return $resolved;
    }

    /**
     * Record an audit event for a persisted snapshot; never break on audit failure.
     *
     * @param SecurityScoreSnapshotInterface $snapshot
     * @return void
     */
    private function recordSnapshotAudit(SecurityScoreSnapshotInterface $snapshot): void
    {
        try {
            $this->auditLogger->record(
                AuditLoggerInterface::EVENT_SECURITY_SCORE_SNAPSHOT,
                [
                    AuditLoggerInterface::CONTEXT_METADATA => [
                        'snapshot_id' => $snapshot->getId(),
                        'score' => $snapshot->getScore(),
                        'label' => $snapshot->getLabel(),
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to record audit event for security score snapshot: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
