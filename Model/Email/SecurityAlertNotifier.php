<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Email;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;

/**
 * Sends the branded security-alert email for critical security events.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class SecurityAlertNotifier
{
    private const DEFAULT_TEMPLATE = 'adminpasskey_email_templates_security_alert_template';

    public function __construct(
        private readonly BrandedEmailSenderInterface $emailSender,
        private readonly ConfigProvider $configProvider
    ) {
    }

    /**
     * Notify a recipient of a critical security event.
     *
     * @param string $email Recipient address.
     * @param string $name Recipient display name.
     * @param string $eventType Human-readable event type.
     * @param string $severity Event severity.
     * @param string $detectedAt When the event was detected.
     * @param int|null $storeId
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function notify(
        string $email,
        string $name,
        string $eventType,
        string $severity,
        string $detectedAt,
        ?int $storeId = null
    ): void {
        $this->emailSender->send(
            $this->resolveTemplate(),
            $email,
            $name,
            [
                'admin_name' => $name,
                'event_type' => $eventType,
                'severity' => $severity,
                'detected_at' => $detectedAt,
            ],
            $storeId
        );
    }

    /**
     * Resolve the configured security-alert template id, falling back to the shipped default.
     */
    private function resolveTemplate(): string
    {
        $template = $this->configProvider->getSecurityAlertEmailTemplate();

        return $template !== '' ? $template : self::DEFAULT_TEMPLATE;
    }
}
