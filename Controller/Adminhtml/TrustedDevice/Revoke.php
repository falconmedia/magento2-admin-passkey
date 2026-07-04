<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\TrustedDevice;

use FalconMedia\AdminPasskey\Model\TrustedDevice\TrustedDeviceManagerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Revoke a single trusted device from the grid.
 *
 * Delegates the revoke (and its audit event) to the trusted-device manager, then
 * redirects back to the grid with a status message. All heavy logic lives in the
 * service, not the controller.
 */
class Revoke extends Action
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::trusted_devices';

    public function __construct(
        Context $context,
        private readonly TrustedDeviceManagerInterface $trustedDeviceManager
    ) {
        parent::__construct($context);
    }

    /**
     * Revoke the requested trusted device.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminpasskey/trusteddevice/index');

        $entityId = (int) $this->getRequest()->getParam('entity_id');
        if ($entityId <= 0) {
            $this->messageManager->addErrorMessage((string) __('No trusted device was specified.'));

            return $resultRedirect;
        }

        try {
            $actorId = (int) $this->_auth->getUser()->getId();
            $this->trustedDeviceManager->revoke($entityId, $actorId > 0 ? $actorId : null);
            $this->messageManager->addSuccessMessage((string) __('The trusted device has been revoked.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('The trusted device could not be found.'));
        } catch (\Throwable) {
            $this->messageManager->addErrorMessage(
                (string) __('The trusted device could not be revoked. Please try again.')
            );
        }

        return $resultRedirect;
    }
}
