<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Migration;

use FalconMedia\AdminPasskey\Model\Migration\ReminderServiceInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

/**
 * Mass action: send passkey migration reminders to the selected admins.
 */
class MassSendReminder extends Action implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::migration';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReminderServiceInterface $reminderService
    ) {
        parent::__construct($context);
    }

    /**
     * Send reminders to the selected admin users.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $adminIds = array_map('intval', $collection->getAllIds());
            $result = $this->reminderService->sendBulkReminders($adminIds, $this->resolveActorId());

            $this->messageManager->addSuccessMessage(
                (string) __(
                    'Reminders sent: %1, skipped: %2, failed: %3.',
                    $result['sent'],
                    $result['skipped'],
                    $result['failed']
                )
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not send the reminders: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/index');
    }

    /**
     * Resolve the acting admin user id, if available.
     *
     * @return int|null
     */
    private function resolveActorId(): ?int
    {
        $user = $this->_auth->getUser();

        return $user !== null ? (int) $user->getId() : null;
    }
}
