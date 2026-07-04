<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Migration;

use FalconMedia\AdminPasskey\Api\Data\ReminderInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Sends passkey migration reminders to administrators without a passkey.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface ReminderServiceInterface
{
    /**
     * Send a single migration reminder to an admin user.
     *
     * @param int $adminUserId
     * @param int|null $actorAdminUserId Admin performing the action, when known.
     * @return ReminderInterface The persisted reminder record.
     * @throws LocalizedException When reminders are disabled, the admin already has a passkey, or sending fails.
     */
    public function sendReminder(int $adminUserId, ?int $actorAdminUserId = null): ReminderInterface;

    /**
     * Send migration reminders to many admins, skipping those that already have a passkey.
     *
     * @param int[] $adminUserIds
     * @param int|null $actorAdminUserId
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function sendBulkReminders(array $adminUserIds, ?int $actorAdminUserId = null): array;
}
