<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Diagnostics;

use FalconMedia\AdminPasskey\Model\Diagnostics\DiagnosticsServiceInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Generate a new diagnostics support bundle.
 */
class Generate extends Action implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::diagnostics';

    public function __construct(
        Context $context,
        private readonly DiagnosticsServiceInterface $diagnosticsService
    ) {
        parent::__construct($context);
    }

    /**
     * Generate the bundle and redirect back to the grid.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $report = $this->diagnosticsService->generate($this->resolveActorId());
            $this->messageManager->addSuccessMessage(
                (string) __('Diagnostics bundle %1 was generated.', (string) $report->getSupportReferenceId())
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not generate the bundle: %1', $e->getMessage()));
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
