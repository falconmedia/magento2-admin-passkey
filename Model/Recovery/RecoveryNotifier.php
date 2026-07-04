<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Recovery;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Email\BrandedEmailSenderInterface;

/**
 * Sends the branded emergency-recovery notification email.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class RecoveryNotifier
{
    private const DEFAULT_TEMPLATE = 'adminpasskey_email_templates_recovery_template';

    public function __construct(
        private readonly BrandedEmailSenderInterface $emailSender,
        private readonly ConfigProvider $configProvider
    ) {
    }

    /**
     * Notify an administrator that emergency recovery mode changed state.
     *
     * @param string $email Recipient address.
     * @param string $name Recipient display name.
     * @param bool $enabled Whether recovery was enabled (true) or disabled (false).
     * @param string|null $reason Optional non-sensitive reason.
     * @param int|null $storeId
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function notifyStateChange(
        string $email,
        string $name,
        bool $enabled,
        ?string $reason = null,
        ?int $storeId = null
    ): void {
        $this->emailSender->send(
            $this->resolveTemplate(),
            $email,
            $name,
            [
                'admin_name' => $name,
                'state' => $enabled ? (string) __('enabled') : (string) __('disabled'),
                'reason' => $reason ?? '',
            ],
            $storeId
        );
    }

    /**
     * Resolve the configured recovery template id, falling back to the shipped default.
     */
    private function resolveTemplate(): string
    {
        $template = $this->configProvider->getRecoveryEmailTemplate();

        return $template !== '' ? $template : self::DEFAULT_TEMPLATE;
    }
}
