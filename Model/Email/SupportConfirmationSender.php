<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Email;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;

/**
 * Sends the branded support-confirmation email after a support request.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class SupportConfirmationSender
{
    private const DEFAULT_TEMPLATE = 'adminpasskey_email_templates_support_template';

    public function __construct(
        private readonly BrandedEmailSenderInterface $emailSender,
        private readonly ConfigProvider $configProvider
    ) {
    }

    /**
     * Confirm receipt of a support request to the requesting administrator.
     *
     * @param string $email Recipient address.
     * @param string $name Recipient display name.
     * @param string $supportReferenceId Support reference identifier.
     * @param int|null $storeId
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function send(string $email, string $name, string $supportReferenceId, ?int $storeId = null): void
    {
        $this->emailSender->send(
            $this->resolveTemplate(),
            $email,
            $name,
            ['admin_name' => $name, 'support_reference_id' => $supportReferenceId],
            $storeId
        );
    }

    /**
     * Resolve the configured support template id, falling back to the shipped default.
     */
    private function resolveTemplate(): string
    {
        $template = $this->configProvider->getSupportEmailTemplate();

        return $template !== '' ? $template : self::DEFAULT_TEMPLATE;
    }
}
