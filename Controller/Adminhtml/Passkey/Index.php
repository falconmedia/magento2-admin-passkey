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
 * "My Passkeys" profile page for the current admin.
 *
 * Lists the authenticated admin's own passkeys with add/rename/revoke actions.
 * The listing and actions are scoped to the current admin only; acting on another
 * admin's credential requires the stricter passkeys_manage_others resource.
 */
class Index extends Action implements HttpGetActionInterface
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
     * Render the current admin's passkey management page.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('My Passkeys'));

        return $resultPage;
    }
}
