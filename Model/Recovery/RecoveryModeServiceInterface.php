<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Recovery;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Emergency recovery-mode orchestration.
 *
 * Recovery mode is the documented escape path that prevents passkey enforcement
 * or lockouts from permanently locking every administrator out. Every enable and
 * disable is persisted and audited; recovery never bypasses audit logging.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface RecoveryModeServiceInterface
{
    /**
     * Enable emergency recovery mode.
     *
     * @param string|null $reason Non-sensitive operator-supplied reason.
     * @param int|null $actorAdminUserId Admin performing the action, when known.
     * @return RecoveryStateInterface The persisted state record.
     * @throws LocalizedException When recovery is disabled by config or already active.
     * @throws CouldNotSaveException
     */
    public function enable(?string $reason = null, ?int $actorAdminUserId = null): RecoveryStateInterface;

    /**
     * Disable emergency recovery mode.
     *
     * @param string|null $reason Non-sensitive operator-supplied reason.
     * @param int|null $actorAdminUserId Admin performing the action, when known.
     * @return RecoveryStateInterface The persisted state record.
     * @throws LocalizedException When recovery is not currently active.
     * @throws CouldNotSaveException
     */
    public function disable(?string $reason = null, ?int $actorAdminUserId = null): RecoveryStateInterface;

    /**
     * Whether recovery mode is currently active (enabled and not auto-expired).
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * The current (most recent) recovery-state record, or null when none exists.
     *
     * @return RecoveryStateInterface|null
     */
    public function getCurrent(): ?RecoveryStateInterface;
}
