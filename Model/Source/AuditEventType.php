<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Source;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option source for the audit event-type grid filter.
 */
class AuditEventType implements OptionSourceInterface
{
    /**
     * @inheritdoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => AuditLoggerInterface::EVENT_PASSKEY_REGISTRATION, 'label' => __('Passkey Registration')],
            ['value' => AuditLoggerInterface::EVENT_PASSKEY_LOGIN, 'label' => __('Passkey Login')],
            ['value' => AuditLoggerInterface::EVENT_PASSWORD_LOGIN, 'label' => __('Password Login')],
            ['value' => AuditLoggerInterface::EVENT_LOGIN_FAILED, 'label' => __('Login Failed')],
            ['value' => AuditLoggerInterface::EVENT_PASSKEY_REVOKE, 'label' => __('Passkey Revoked')],
            ['value' => AuditLoggerInterface::EVENT_PASSKEY_NAME_UPDATED, 'label' => __('Passkey Name Updated')],
            ['value' => AuditLoggerInterface::EVENT_TRUSTED_DEVICE_CREATED, 'label' => __('Trusted Device Created')],
            ['value' => AuditLoggerInterface::EVENT_TRUSTED_DEVICE_REVOKE, 'label' => __('Trusted Device Revoked')],
            ['value' => AuditLoggerInterface::EVENT_TRUSTED_DEVICE_EXPIRED, 'label' => __('Trusted Device Expired')],
            ['value' => AuditLoggerInterface::EVENT_LOCKOUT, 'label' => __('Account Lockout')],
            ['value' => AuditLoggerInterface::EVENT_UNLOCK, 'label' => __('Account Unlock')],
            ['value' => AuditLoggerInterface::EVENT_BRUTE_FORCE, 'label' => __('Brute Force Detected')],
            ['value' => AuditLoggerInterface::EVENT_RECOVERY_ENABLE, 'label' => __('Recovery Mode Enabled')],
            ['value' => AuditLoggerInterface::EVENT_RECOVERY_DISABLE, 'label' => __('Recovery Mode Disabled')],
            [
                'value' => AuditLoggerInterface::EVENT_DIAGNOSTICS_GENERATE,
                'label' => __('Diagnostics Report Generated'),
            ],
            ['value' => AuditLoggerInterface::EVENT_DIAGNOSTICS_SEND, 'label' => __('Diagnostics Report Sent')],
            ['value' => AuditLoggerInterface::EVENT_CLEANUP, 'label' => __('Data Cleanup')],
            ['value' => AuditLoggerInterface::EVENT_MIGRATION_REMINDER, 'label' => __('Migration Reminder Sent')],
            [
                'value' => AuditLoggerInterface::EVENT_SECURITY_SCORE_SNAPSHOT,
                'label' => __('Security Score Snapshot'),
            ],
            ['value' => AuditLoggerInterface::EVENT_CONFIG_CHANGE, 'label' => __('Configuration Change')],
        ];
    }
}
