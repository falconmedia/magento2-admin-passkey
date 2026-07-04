<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Lockout;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\Data\LockoutInterfaceFactory;
use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Fail2Ban\Fail2BanLoggerInterface;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Default durable lockout orchestration.
 *
 * Builds on the failed-attempt seam: every failure funnels through here, is
 * counted within the configured rolling window (per admin id, username, or IP),
 * and raises a persistent lockout once the threshold is reached. Lockouts block
 * both passkey and password login until they expire or an admin unlocks them.
 * When emergency recovery mode is active, blocking is suppressed so admins can
 * always regain access. Lockout and brute-force events are audited and written to
 * the Fail2Ban log.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class LockoutManager implements LockoutManagerInterface
{
    /**
     * Lockout reasons.
     */
    private const REASON_TRACKING = 'failed_login';
    private const REASON_LOCKED = 'max_failed_attempts';

    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly LockoutRepositoryInterface $lockoutRepository,
        private readonly LockoutInterfaceFactory $lockoutFactory,
        private readonly LockoutEvaluator $evaluator,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly Fail2BanLoggerInterface $fail2BanLogger,
        private readonly RecoveryModeServiceInterface $recoveryModeService,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function registerFailedAttempt(
        ?int $adminUserId,
        ?string $username,
        ?string $ip,
        string $method
    ): void {
        try {
            $this->fail2BanLogger->log(AuditLoggerInterface::EVENT_LOGIN_FAILED, $ip, $username, $adminUserId, $method);

            if (!$this->configProvider->isLockoutEnabled() || $this->recoveryModeService->isActive()) {
                return;
            }

            $now = $this->now();
            $row = $this->findTrackingRow($adminUserId, $username, $ip);
            $wasLocked = false;
            $previousFailed = 0;

            if ($row !== null) {
                $previousFailed = (int) $row->getFailedAttempts();
                $wasLocked = $this->evaluator->isCurrentlyLocked($row->getStatus(), $row->getLockedUntil(), $now);
                if ($this->evaluator->isWithinWindow((string) $row->getCreatedAt(), $now, $this->windowMinutes())) {
                    $failed = $previousFailed + 1;
                } else {
                    $failed = 1;
                    $previousFailed = 0;
                    $wasLocked = false;
                    $row->setCreatedAt($now);
                    $row->setLockedUntil(null);
                }
            } else {
                $row = $this->createTrackingRow($adminUserId, $username, $ip);
                $failed = 1;
            }

            $row->setFailedAttempts($failed);
            $row->setReason(self::REASON_TRACKING);

            $maxAttempts = $this->configProvider->getMaxFailedAttempts();
            if ($this->evaluator->shouldLock($failed, $maxAttempts)) {
                $this->applyLock($row, $now, $failed, $previousFailed, $wasLocked, $adminUserId, $username, $ip, $method);

                return;
            }

            $this->lockoutRepository->save($row);
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey lockout handling failed: ' . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function registerSuccessfulAttempt(?int $adminUserId, ?string $username, ?string $ip): void
    {
        try {
            if (!$this->configProvider->isLockoutEnabled()) {
                return;
            }

            $row = $this->findTrackingRow($adminUserId, $username, $ip);
            if ($row === null) {
                return;
            }

            $row->setStatus(LockoutInterface::STATUS_RELEASED);
            $row->setUnlockedAt($this->now());
            $this->lockoutRepository->save($row);
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey lockout reset failed: ' . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function isLocked(?int $adminUserId, ?string $username, ?string $ip): bool
    {
        if (!$this->configProvider->isLockoutEnabled() || $this->recoveryModeService->isActive()) {
            return false;
        }

        $now = $this->now();
        foreach ($this->candidateRows($adminUserId, $username, $ip) as $row) {
            if ($this->evaluator->isCurrentlyLocked($row->getStatus(), $row->getLockedUntil(), $now)) {
                return true;
            }
            $this->autoRelease($row, $now);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function unlock(int $entityId, ?int $actorAdminUserId = null): void
    {
        $lockout = $this->lockoutRepository->getById($entityId);
        $lockout->setStatus(LockoutInterface::STATUS_RELEASED);
        $lockout->setUnlockedAt($this->now());
        $lockout->setUnlockedBy($actorAdminUserId);
        $this->lockoutRepository->save($lockout);

        $this->audit(AuditLoggerInterface::EVENT_UNLOCK, $lockout, $actorAdminUserId, null);
    }

    /**
     * Lock the row and record audit + Fail2Ban entries on transition.
     *
     * @param LockoutInterface $row
     * @param string $now
     * @param int $failed
     * @param int $previousFailed
     * @param bool $wasLocked
     * @param int|null $adminUserId
     * @param string|null $username
     * @param string|null $ip
     * @param string $method
     * @return void
     */
    private function applyLock(
        LockoutInterface $row,
        string $now,
        int $failed,
        int $previousFailed,
        bool $wasLocked,
        ?int $adminUserId,
        ?string $username,
        ?string $ip,
        string $method
    ): void {
        $maxAttempts = $this->configProvider->getMaxFailedAttempts();
        $row->setLockedUntil($this->evaluator->computeLockedUntil($now, $this->configProvider->getLockoutDurationMinutes()));
        $row->setReason(self::REASON_LOCKED);
        $saved = $this->lockoutRepository->save($row);

        if (!$wasLocked) {
            $this->audit(AuditLoggerInterface::EVENT_LOCKOUT, $saved, $adminUserId, $method);
            $this->fail2BanLogger->log(AuditLoggerInterface::EVENT_LOCKOUT, $ip, $username, $adminUserId, $method);
        }

        if ($this->evaluator->isBruteForce($failed, $maxAttempts)
            && !$this->evaluator->isBruteForce($previousFailed, $maxAttempts)
        ) {
            $this->audit(AuditLoggerInterface::EVENT_BRUTE_FORCE, $saved, $adminUserId, $method);
            $this->fail2BanLogger->log(AuditLoggerInterface::EVENT_BRUTE_FORCE, $ip, $username, $adminUserId, $method);
        }
    }

    /**
     * Find an existing active tracking/lockout row for the strongest available identity.
     *
     * @param int|null $adminUserId
     * @param string|null $username
     * @param string|null $ip
     * @return LockoutInterface|null
     */
    private function findTrackingRow(?int $adminUserId, ?string $username, ?string $ip): ?LockoutInterface
    {
        if ($adminUserId !== null && $adminUserId > 0) {
            $row = $this->lockoutRepository->findActiveForAdmin($adminUserId);
            if ($row !== null) {
                return $row;
            }
        }
        if ($username !== null && $username !== '') {
            $row = $this->lockoutRepository->findActiveForUsername($username);
            if ($row !== null) {
                return $row;
            }
        }
        if ($ip !== null && $ip !== '') {
            $row = $this->lockoutRepository->findActiveForIp($ip);
            if ($row !== null) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Build a fresh active tracking row for the supplied identity.
     *
     * @param int|null $adminUserId
     * @param string|null $username
     * @param string|null $ip
     * @return LockoutInterface
     */
    private function createTrackingRow(?int $adminUserId, ?string $username, ?string $ip): LockoutInterface
    {
        /** @var LockoutInterface $row */
        $row = $this->lockoutFactory->create();
        $row->setAdminUserId($adminUserId !== null && $adminUserId > 0 ? $adminUserId : null);
        $row->setUsername($username !== null && $username !== '' ? $username : null);
        $row->setIp($ip !== null && $ip !== '' ? $ip : null);
        $row->setStatus(LockoutInterface::STATUS_ACTIVE);

        return $row;
    }

    /**
     * Collect the distinct active rows relevant to the supplied identifiers.
     *
     * @param int|null $adminUserId
     * @param string|null $username
     * @param string|null $ip
     * @return LockoutInterface[]
     */
    private function candidateRows(?int $adminUserId, ?string $username, ?string $ip): array
    {
        $rows = [];
        if ($adminUserId !== null && $adminUserId > 0) {
            $this->collect($rows, $this->lockoutRepository->findActiveForAdmin($adminUserId));
        }
        if ($username !== null && $username !== '') {
            $this->collect($rows, $this->lockoutRepository->findActiveForUsername($username));
        }
        if ($ip !== null && $ip !== '') {
            $this->collect($rows, $this->lockoutRepository->findActiveForIp($ip));
        }

        return array_values($rows);
    }

    /**
     * Add a row to the accumulator keyed by id (de-duplicating).
     *
     * @param array<int, LockoutInterface> $rows
     * @param LockoutInterface|null $row
     * @return void
     */
    private function collect(array &$rows, ?LockoutInterface $row): void
    {
        if ($row === null) {
            return;
        }
        $id = $row->getId();
        if ($id !== null) {
            $rows[$id] = $row;
        }
    }

    /**
     * Release a lockout row whose lock has already expired.
     *
     * @param LockoutInterface $row
     * @param string $now
     * @return void
     */
    private function autoRelease(LockoutInterface $row, string $now): void
    {
        if ($row->getStatus() !== LockoutInterface::STATUS_ACTIVE
            || $row->getLockedUntil() === null
            || $this->evaluator->isCurrentlyLocked($row->getStatus(), $row->getLockedUntil(), $now)
        ) {
            return;
        }

        $row->setStatus(LockoutInterface::STATUS_RELEASED);
        $row->setUnlockedAt($now);
        $this->lockoutRepository->save($row);
    }

    /**
     * Record a lockout audit event, swallowing audit errors.
     *
     * @param string $eventType
     * @param LockoutInterface $row
     * @param int|null $actorAdminUserId
     * @param string|null $method
     * @return void
     */
    private function audit(string $eventType, LockoutInterface $row, ?int $actorAdminUserId, ?string $method): void
    {
        try {
            $context = [
                AuditLoggerInterface::CONTEXT_TARGET => $row->getAdminUserId(),
                AuditLoggerInterface::CONTEXT_METADATA => [
                    'lockout_row_id' => $row->getId(),
                    'username' => $row->getUsername(),
                    'ip' => $row->getIp(),
                    'failed_attempts' => $row->getFailedAttempts(),
                    'locked_until' => $row->getLockedUntil(),
                    'method' => $method,
                ],
            ];
            if ($actorAdminUserId !== null) {
                $context[AuditLoggerInterface::CONTEXT_ACTOR] = $actorAdminUserId;
            }
            if ($row->getIp() !== null) {
                $context[AuditLoggerInterface::CONTEXT_IP] = $row->getIp();
            }
            $this->auditLogger->record($eventType, $context);
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey lockout audit failed: ' . $e->getMessage());
        }
    }

    /**
     * Configured rolling window length in minutes.
     *
     * @return int
     */
    private function windowMinutes(): int
    {
        return $this->configProvider->getAttemptWindowMinutes();
    }

    /**
     * Current UTC timestamp (Y-m-d H:i:s).
     *
     * @return string
     */
    private function now(): string
    {
        return $this->dateTime->gmtDate();
    }
}
