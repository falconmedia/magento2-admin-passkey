<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Cleanup;

use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;

/**
 * Retention cleanup for expired module data.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface CleanupServiceInterface
{
    /**
     * Run cleanup across all configured categories and record a log entry.
     *
     * @param int|null $actorAdminUserId Admin performing a manual run, when applicable.
     * @return CleanupLogInterface
     */
    public function run(?int $actorAdminUserId = null): CleanupLogInterface;
}
