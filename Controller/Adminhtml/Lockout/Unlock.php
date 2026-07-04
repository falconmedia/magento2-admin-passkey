<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Lockout;

use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Release a single lockout from the grid.
 *
 * Delegates the release (status released, unlocked_at/by, audit) to the lockout
 * manager, then redirects back to the grid with a status message. All heavy logic
 * lives in the service, not the controller.
 */
class Unlock extends Action
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::lockouts';

    public function __construct(
        Context $context,
        private readonly LockoutManagerInterface $lockoutManager
    ) {
        parent::__construct($context);
    }

    /**
     * Release the requested lockout.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminpasskey/lockout/index');

        $entityId = (int) $this->getRequest()->getParam('entity_id');
        if ($entityId <= 0) {
            $this->messageManager->addErrorMessage((string) __('No lockout was specified.'));

            return $resultRedirect;
        }

        try {
            $actorId = (int) $this->_auth->getUser()->getId();
            $this->lockoutManager->unlock($entityId, $actorId > 0 ? $actorId : null);
            $this->messageManager->addSuccessMessage((string) __('The lockout has been released.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('The lockout could not be found.'));
        } catch (\Throwable) {
            $this->messageManager->addErrorMessage(
                (string) __('The lockout could not be released. Please try again.')
            );
        }

        return $resultRedirect;
    }
}
