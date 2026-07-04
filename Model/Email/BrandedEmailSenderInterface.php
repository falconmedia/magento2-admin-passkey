<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Email;

use Magento\Framework\Exception\MailException;

/**
 * Reusable sender for branded module emails.
 *
 * Wraps the native TransportBuilder and injects the shared branding variables so
 * every notification (lockout, recovery, diagnostics, support, security alerts)
 * renders consistently with the configured white-label branding.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface BrandedEmailSenderInterface
{
    /**
     * Send a branded transactional email.
     *
     * @param string $templateId Email template identifier.
     * @param string $toEmail Recipient address.
     * @param string $toName Recipient display name.
     * @param array<string, mixed> $vars Template-specific variables (merged over branding vars).
     * @param int|null $storeId Store scope, defaults to the admin/default store.
     * @return void
     * @throws MailException
     */
    public function send(string $templateId, string $toEmail, string $toName, array $vars = [], ?int $storeId = null): void;
}
