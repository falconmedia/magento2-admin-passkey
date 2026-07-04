<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\SecurityScore;

use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreServiceInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Recalculate the security score and persist a snapshot.
 */
class Snapshot extends Action implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::security_score';

    public function __construct(
        Context $context,
        private readonly SecurityScoreServiceInterface $securityScoreService
    ) {
        parent::__construct($context);
    }

    /**
     * Capture a snapshot and redirect back to the page.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $snapshot = $this->securityScoreService->snapshot();
            $this->messageManager->addSuccessMessage(
                (string) __('Security score snapshot saved: %1 (%2).', $snapshot->getScore(), $snapshot->getLabel())
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not capture a snapshot: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
