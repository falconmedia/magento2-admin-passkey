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
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;

/**
 * Download a generated diagnostics bundle (read-only).
 */
class Download extends Action implements HttpGetActionInterface
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::diagnostics';

    public function __construct(
        Context $context,
        private readonly DiagnosticsServiceInterface $diagnosticsService,
        private readonly FileFactory $fileFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Stream the requested bundle as a file download.
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute(): ResponseInterface|ResultInterface
    {
        $reportId = (int) $this->getRequest()->getParam('id');

        try {
            $relPath = $this->diagnosticsService->getReportArchiveRelativePath($reportId);

            return $this->fileFactory->create(
                basename($relPath),
                ['type' => 'filename', 'value' => $relPath],
                DirectoryList::VAR_DIR,
                'application/zip'
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not download the bundle: %1', $e->getMessage()));

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('*/*/index');
        }
    }
}
