<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Cleanup;

use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;
use FalconMedia\AdminPasskey\Model\Cleanup\CleanupServiceInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Manually run the retention cleanup.
 */
class Run extends Action implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::diagnostics';

    public function __construct(
        Context $context,
        private readonly CleanupServiceInterface $cleanupService
    ) {
        parent::__construct($context);
    }

    /**
     * Execute cleanup and redirect back with a result message.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $log = $this->cleanupService->run($this->resolveActorId());
            if ($log->getStatus() === CleanupLogInterface::STATUS_SUCCESS) {
                $this->messageManager->addSuccessMessage(__('Cleanup completed successfully.'));
            } else {
                $this->messageManager->addWarningMessage(
                    __('Cleanup completed with errors. Check the cleanup log for details.')
                );
            }
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not run cleanup: %1', $e->getMessage()));
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
