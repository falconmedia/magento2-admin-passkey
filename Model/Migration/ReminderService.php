<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Migration;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ReminderInterface;
use FalconMedia\AdminPasskey\Api\Data\ReminderInterfaceFactory;
use FalconMedia\AdminPasskey\Api\ReminderRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Email\BrandedEmailVariables;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\Store;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;

/**
 * Sends passkey migration reminders and records each attempt (with audit).
 * All heavy logic lives here so controllers/mass actions merely delegate.
 */
class ReminderService implements ReminderServiceInterface
{
    /**
     * Email identity used as the sender scope.
     */
    private const EMAIL_SENDER = 'general';

    public function __construct(
        private readonly ReminderRepositoryInterface $reminderRepository,
        private readonly ReminderInterfaceFactory $reminderFactory,
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly UserFactory $userFactory,
        private readonly ConfigProvider $configProvider,
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly LoggerInterface $logger,
        private readonly BrandedEmailVariables $brandedEmailVariables
    ) {
    }

    /**
     * @inheritdoc
     */
    public function sendReminder(int $adminUserId, ?int $actorAdminUserId = null): ReminderInterface
    {
        if (!$this->configProvider->isMigrationReminderEmailEnabled()) {
            throw new LocalizedException(__('Reminder emails are disabled in configuration.'));
        }

        $user = $this->loadUser($adminUserId);
        $email = (string) $user->getEmail();
        if ($email === '') {
            throw new LocalizedException(__('This administrator has no email address on file.'));
        }
        if ($this->hasActivePasskey($adminUserId)) {
            throw new LocalizedException(__('This administrator already has an active passkey.'));
        }

        try {
            $this->sendEmail($email, (string) $user->getName());
            $reminder = $this->recordReminder($adminUserId, ReminderInterface::STATUS_SENT);
            $this->recordAudit($adminUserId, $actorAdminUserId, ReminderInterface::STATUS_SENT);

            return $reminder;
        } catch (\Throwable $e) {
            $this->recordReminder($adminUserId, ReminderInterface::STATUS_FAILED);
            $this->recordAudit($adminUserId, $actorAdminUserId, ReminderInterface::STATUS_FAILED);
            $this->logger->error('Failed to send passkey migration reminder: ' . $e->getMessage(), ['exception' => $e]);

            throw new LocalizedException(__('The reminder email could not be sent. Please try again.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function sendBulkReminders(array $adminUserIds, ?int $actorAdminUserId = null): array
    {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($adminUserIds as $adminUserId) {
            $id = (int) $adminUserId;
            if ($id <= 0 || $this->hasActivePasskey($id)) {
                $skipped++;
                continue;
            }

            try {
                $this->sendReminder($id, $actorAdminUserId);
                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * Load an admin user by id.
     *
     * @param int $adminUserId
     * @return User
     * @throws LocalizedException
     */
    private function loadUser(int $adminUserId): User
    {
        /** @var User $user */
        $user = $this->userFactory->create();
        $user->load($adminUserId);
        if ((int) $user->getId() !== $adminUserId || $adminUserId <= 0) {
            throw new LocalizedException(__('The administrator could not be found.'));
        }

        return $user;
    }

    /**
     * Whether the admin user has at least one active passkey.
     *
     * @param int $adminUserId
     * @return bool
     */
    private function hasActivePasskey(int $adminUserId): bool
    {
        try {
            return $this->credentialRepository->listActiveForAdmin($adminUserId)->getTotalCount() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Send the reminder email via the native transport builder.
     *
     * @param string $email
     * @param string $name
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    private function sendEmail(string $email, string $name): void
    {
        $this->inlineTranslation->suspend();
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($this->resolveTemplateId())
                ->setTemplateOptions(['area' => Area::AREA_ADMINHTML, 'store' => Store::DEFAULT_STORE_ID])
                ->setTemplateVars($this->brandedEmailVariables->build(['admin_name' => $name]))
                ->setFromByScope(self::EMAIL_SENDER, Store::DEFAULT_STORE_ID)
                ->addTo($email, $name)
                ->getTransport();
            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    /**
     * Resolve the configured reminder template identifier.
     *
     * @return string
     */
    private function resolveTemplateId(): string
    {
        $template = $this->configProvider->getReminderEmailTemplate();

        return $template !== '' ? $template : 'adminpasskey_email_templates_reminder_template';
    }

    /**
     * Persist a reminder record.
     *
     * @param int $adminUserId
     * @param string $status
     * @return ReminderInterface
     */
    private function recordReminder(int $adminUserId, string $status): ReminderInterface
    {
        /** @var ReminderInterface $reminder */
        $reminder = $this->reminderFactory->create();
        $reminder->setAdminUserId($adminUserId);
        $reminder->setReminderType(ReminderInterface::TYPE_MIGRATION_PASSKEY);
        $reminder->setStatus($status);
        if ($status === ReminderInterface::STATUS_SENT) {
            $reminder->setSentAt(gmdate('Y-m-d H:i:s'));
        }

        return $this->reminderRepository->save($reminder);
    }

    /**
     * Record an audit event for the reminder; never break on audit failure.
     *
     * @param int $adminUserId
     * @param int|null $actorAdminUserId
     * @param string $status
     * @return void
     */
    private function recordAudit(int $adminUserId, ?int $actorAdminUserId, string $status): void
    {
        try {
            $context = [
                AuditLoggerInterface::CONTEXT_TARGET => $adminUserId,
                AuditLoggerInterface::CONTEXT_METADATA => [
                    'reminder_type' => ReminderInterface::TYPE_MIGRATION_PASSKEY,
                    'status' => $status,
                ],
            ];
            if ($actorAdminUserId !== null) {
                $context[AuditLoggerInterface::CONTEXT_ACTOR] = $actorAdminUserId;
            }

            $this->auditLogger->record(AuditLoggerInterface::EVENT_MIGRATION_REMINDER, $context);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to record audit event for passkey migration reminder: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
