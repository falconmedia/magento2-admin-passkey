<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Health;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\RelyingPartyProvider;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Gathers environment facts and delegates the pass/fail decisions to
 * {@see HealthCheckEvaluator}. The resulting report is reused by the diagnostics
 * bundle and the security score engine.
 */
class HealthCheckService implements HealthCheckServiceInterface
{
    /**
     * Minimum supported PHP version.
     */
    private const MIN_PHP_VERSION = '8.1.0';

    /**
     * Minimum recommended Magento version.
     */
    private const MIN_MAGENTO_VERSION = '2.4.6';

    /**
     * Cron freshness threshold in minutes.
     */
    private const CRON_MAX_AGE_MINUTES = 120;

    /**
     * Active-lockout count at which a warning is raised.
     */
    private const LOCKOUT_WARN_THRESHOLD = 5;

    /**
     * HSTS enable configuration path.
     */
    private const XML_PATH_ENABLE_HSTS = 'web/secure/enable_hsts';

    public function __construct(
        private readonly HealthCheckEvaluator $evaluator,
        private readonly ConfigProvider $configProvider,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly RelyingPartyProvider $relyingPartyProvider,
        private readonly RecoveryModeServiceInterface $recoveryModeService,
        private readonly LockoutRepositoryInterface $lockoutRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly ResourceConnection $resourceConnection,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @inheritdoc
     */
    public function run(): HealthReport
    {
        $isSecure = str_starts_with($this->relyingPartyProvider->getOrigin(), 'https://');
        [$cronConfigured, $minutesSinceCron] = $this->resolveCronState();

        $results = [
            $this->evaluator->evaluatePhpVersion(PHP_VERSION, self::MIN_PHP_VERSION),
            $this->evaluator->evaluateMagentoVersion(
                (string) $this->productMetadata->getVersion(),
                self::MIN_MAGENTO_VERSION
            ),
            $this->evaluator->evaluateHttps($isSecure),
            $this->evaluator->evaluateWebAuthn($this->relyingPartyProvider->getId(), $isSecure),
            $this->evaluator->evaluateHsts($this->resolveHstsMarker()),
            $this->evaluator->evaluateCron($cronConfigured, $minutesSinceCron, self::CRON_MAX_AGE_MINUTES),
            $this->evaluator->evaluateAuditLogging($this->configProvider->isEnabled()),
            $this->evaluator->evaluateCleanupConfig(
                $this->configProvider->isCleanupEnabled(),
                $this->configProvider->getChallengeRetentionDays()
            ),
            $this->evaluator->evaluateDiagnosticsConfig(
                $this->configProvider->isDiagnosticsEnabled(),
                $this->configProvider->getDiagnosticsSupportEmail()
            ),
            $this->evaluator->evaluateRecoveryState($this->isRecoveryActive()),
            $this->evaluator->evaluateLockoutHealth($this->countActiveLockouts(), self::LOCKOUT_WARN_THRESHOLD),
            $this->evaluator->evaluateConfigSanity($this->resolveWeightSum()),
        ];

        return new HealthReport($results);
    }

    /**
     * Resolve an HSTS marker string, or null when it is not enabled/detectable.
     *
     * @return string|null
     */
    private function resolveHstsMarker(): ?string
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_HSTS) ? 'enabled' : null;
    }

    /**
     * Sum of the four configured security score weights.
     *
     * @return int
     */
    private function resolveWeightSum(): int
    {
        return $this->configProvider->getSecurityScoreWeightAuthentication()
            + $this->configProvider->getSecurityScoreWeightSecurity()
            + $this->configProvider->getSecurityScoreWeightOperational()
            + $this->configProvider->getSecurityScoreWeightThreats();
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
     * Count currently active lockouts, failing safe to zero.
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
     * Resolve whether cron is configured and how long ago it last succeeded.
     *
     * @return array{0: bool, 1: int|null}
     */
    private function resolveCronState(): array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('cron_schedule');

            $total = (int) $connection->fetchOne(
                $connection->select()->from($table, ['count' => new \Zend_Db_Expr('COUNT(*)')])
            );
            if ($total === 0) {
                return [false, null];
            }

            $lastExecuted = $connection->fetchOne(
                $connection->select()
                    ->from($table, ['executed_at'])
                    ->where('status = ?', 'success')
                    ->where('executed_at IS NOT NULL')
                    ->order('executed_at DESC')
                    ->limit(1)
            );

            if (!is_string($lastExecuted) || $lastExecuted === '') {
                return [true, null];
            }

            $lastTimestamp = strtotime($lastExecuted);
            if ($lastTimestamp === false) {
                return [true, null];
            }

            $minutes = (int) floor(($this->dateTime->gmtTimestamp() - $lastTimestamp) / 60);

            return [true, max(0, $minutes)];
        } catch (\Throwable) {
            return [false, null];
        }
    }
}
