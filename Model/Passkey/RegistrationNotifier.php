<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Passkey;

use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Model\Email\SecurityAlertNotifier;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\User\Model\User;
use Psr\Log\LoggerInterface;

/**
 * Emails the account owner whenever a new passkey is registered on their account.
 *
 * A passkey grants sign-in, so an unexpected registration must be visible to the
 * owner even if it happened through a hijacked session. Mail failures are logged
 * and never break the registration response.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class RegistrationNotifier
{
    public function __construct(
        private readonly SecurityAlertNotifier $securityAlertNotifier,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Send the "new passkey registered" security alert to the admin.
     *
     * @param User $user
     * @return void
     */
    public function notify(User $user): void
    {
        $email = (string) $user->getEmail();
        if ($email === '') {
            return;
        }

        $name = trim((string) $user->getFirstName() . ' ' . (string) $user->getLastName());

        try {
            $this->securityAlertNotifier->notify(
                $email,
                $name !== '' ? $name : (string) $user->getUserName(),
                (string) __('New passkey registered on your Admin account'),
                AuditEventInterface::SEVERITY_WARNING,
                $this->dateTime->gmtDate() . ' UTC'
            );
        } catch (\Throwable $e) {
            $this->logger->error('AdminPasskey passkey registration notification failed: ' . $e->getMessage());
        }
    }
}
