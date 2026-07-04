<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Diagnostics;

use FalconMedia\AdminPasskey\Model\Diagnostics\DiagnosticsServiceInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\DiagnosticsReport\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Mass action: email the selected diagnostics reports to the configured developer.
 */
class MassSend extends Action implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::diagnostics';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly DiagnosticsServiceInterface $diagnosticsService
    ) {
        parent::__construct($context);
    }

    /**
     * Send the selected reports and redirect back to the grid.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $actorId = $this->resolveActorId();
            $sent = 0;
            $failed = 0;
            foreach ($collection->getAllIds() as $id) {
                try {
                    $this->diagnosticsService->send((int) $id, $actorId);
                    $sent++;
                } catch (\Throwable) {
                    $failed++;
                }
            }

            $this->messageManager->addSuccessMessage(
                (string) __('Reports sent: %1, failed: %2.', $sent, $failed)
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not send the reports: %1', $e->getMessage()));
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
