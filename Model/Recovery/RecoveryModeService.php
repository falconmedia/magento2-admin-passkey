<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Recovery;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterfaceFactory;
use FalconMedia\AdminPasskey\Api\RecoveryStateRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Default emergency recovery-mode orchestration.
 *
 * Every state change is persisted as an immutable history row and audited. Audit
 * logging is always invoked on a mutation, so recovery can never bypass the audit
 * trail. Enabling recovery is the documented escape path that guarantees an
 * administrator can regain access even if passkey enforcement or lockouts would
 * otherwise block every admin.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class RecoveryModeService implements RecoveryModeServiceInterface
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly RecoveryStateRepositoryInterface $recoveryStateRepository,
        private readonly RecoveryStateInterfaceFactory $recoveryStateFactory,
        private readonly RecoveryStateTransitionEvaluator $transitionEvaluator,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function enable(?string $reason = null, ?int $actorAdminUserId = null): RecoveryStateInterface
    {
        if (!$this->configProvider->isRecoveryEnabled()) {
            throw new LocalizedException(__('Emergency recovery mode is disabled in configuration.'));
        }
        if (!$this->transitionEvaluator->canEnable($this->isActive())) {
            throw new LocalizedException(__('Emergency recovery mode is already enabled.'));
        }

        $now = $this->now();
        /** @var RecoveryStateInterface $state */
        $state = $this->recoveryStateFactory->create();
        $state->setState(RecoveryStateInterface::STATE_ENABLED);
        $state->setEnabledAt($now);
        $state->setActorAdminUserId($actorAdminUserId);
        $state->setReason($reason);

        $saved = $this->recoveryStateRepository->save($state);
        $this->audit(AuditLoggerInterface::EVENT_RECOVERY_ENABLE, $saved, $actorAdminUserId, $reason);

        return $saved;
    }

    /**
     * @inheritdoc
     */
    public function disable(?string $reason = null, ?int $actorAdminUserId = null): RecoveryStateInterface
    {
        if (!$this->transitionEvaluator->canDisable($this->isActive())) {
            throw new LocalizedException(__('Emergency recovery mode is not currently enabled.'));
        }

        $now = $this->now();
        /** @var RecoveryStateInterface $state */
        $state = $this->recoveryStateFactory->create();
        $state->setState(RecoveryStateInterface::STATE_DISABLED);
        $state->setDisabledAt($now);
        $state->setActorAdminUserId($actorAdminUserId);
        $state->setReason($reason);

        $saved = $this->recoveryStateRepository->save($state);
        $this->audit(AuditLoggerInterface::EVENT_RECOVERY_DISABLE, $saved, $actorAdminUserId, $reason);

        return $saved;
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        if (!$this->configProvider->isRecoveryEnabled()) {
            return false;
        }

        $current = $this->recoveryStateRepository->getCurrent();
        if ($current === null) {
            return false;
        }

        return $this->transitionEvaluator->isActive(
            $current->getState(),
            $current->getEnabledAt(),
            $this->configProvider->getRecoveryExpiryMinutes(),
            $this->now()
        );
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?RecoveryStateInterface
    {
        return $this->recoveryStateRepository->getCurrent();
    }

    /**
     * Record a recovery audit event. A recovery mutation must never proceed without
     * attempting an audit, so any audit failure is logged as critical (not silently
     * dropped), but does not roll back the already-persisted state change.
     *
     * @param string $eventType
     * @param RecoveryStateInterface $state
     * @param int|null $actorAdminUserId
     * @param string|null $reason
     * @return void
     */
    private function audit(
        string $eventType,
        RecoveryStateInterface $state,
        ?int $actorAdminUserId,
        ?string $reason
    ): void {
        try {
            $context = [
                AuditLoggerInterface::CONTEXT_METADATA => [
                    'recovery_state_row_id' => $state->getId(),
                    'state' => $state->getState(),
                    'reason' => $reason,
                ],
            ];
            if ($actorAdminUserId !== null) {
                $context[AuditLoggerInterface::CONTEXT_ACTOR] = $actorAdminUserId;
            }
            $this->auditLogger->record($eventType, $context);
        } catch (\Throwable $e) {
            $this->logger->critical('AdminPasskey recovery audit failed: ' . $e->getMessage());
        }
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
