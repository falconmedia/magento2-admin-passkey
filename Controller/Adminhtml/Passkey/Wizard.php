<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Passkey;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Passkey setup wizard page (Welcome -> Registration -> Success -> Recommendation).
 *
 * The page only renders the wizard shell; the WebAuthn ceremony is driven by a
 * CSP-safe JS component that reuses the existing authenticated register/options
 * and register/verify endpoints plus the rename endpoint. This is also the target
 * of the onboarding-enforcement redirect for admins without a passkey.
 */
class Wizard extends Action implements HttpGetActionInterface
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::passkeys';

    /**
     * Passkey enrolment/management is personal self-service: every authenticated
     * admin manages only their OWN credentials (all actions are scoped to the
     * session admin), so access must never depend on an extra ACL grant — an
     * admin can always secure their own login.
     */
    protected function _isAllowed(): bool
    {
        return true;
    }

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Render the setup wizard page.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Passkey Setup'));

        return $resultPage;
    }
}
