<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Lockout;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Email\BrandedEmailSenderInterface;

/**
 * Sends the branded lockout notification email.
 *
 * A thin wrapper around {@see BrandedEmailSenderInterface} that resolves the
 * configured lockout template and assembles the lockout-specific variables.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class LockoutNotifier
{
    private const DEFAULT_TEMPLATE = 'adminpasskey_email_templates_lockout_template';

    public function __construct(
        private readonly BrandedEmailSenderInterface $emailSender,
        private readonly ConfigProvider $configProvider
    ) {
    }

    /**
     * Notify an administrator that their account has been locked.
     *
     * @param string $email Recipient address.
     * @param string $name Recipient display name.
     * @param string $lockedUntil Lockout expiry timestamp.
     * @param int|null $storeId
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function notifyLocked(string $email, string $name, string $lockedUntil, ?int $storeId = null): void
    {
        $this->emailSender->send(
            $this->resolveTemplate(),
            $email,
            $name,
            ['admin_name' => $name, 'locked_until' => $lockedUntil],
            $storeId
        );
    }

    /**
     * Resolve the configured lockout template id, falling back to the shipped default.
     */
    private function resolveTemplate(): string
    {
        $template = $this->configProvider->getLockoutEmailTemplate();

        return $template !== '' ? $template : self::DEFAULT_TEMPLATE;
    }
}
