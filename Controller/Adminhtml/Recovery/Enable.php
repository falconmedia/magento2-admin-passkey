<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Recovery;

use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;

/**
 * Enable emergency recovery mode.
 *
 * POST-only and form-key protected. The state change and its critical audit event
 * are handled by the recovery service; the controller only validates the request
 * and reports the outcome.
 */
class Enable extends Action implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this controller.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::recovery';

    public function __construct(
        Context $context,
        private readonly RecoveryModeServiceInterface $recoveryModeService,
        private readonly FormKeyValidator $formKeyValidator
    ) {
        parent::__construct($context);
    }

    /**
     * Enable recovery mode.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminpasskey/recovery/index');

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage((string) __('Invalid form key. Please try again.'));

            return $resultRedirect;
        }

        try {
            $actorId = (int) $this->_auth->getUser()->getId();
            $reason = trim((string) $this->getRequest()->getParam('reason', ''));
            $this->recoveryModeService->enable($reason !== '' ? $reason : null, $actorId > 0 ? $actorId : null);
            $this->messageManager->addWarningMessage(
                (string) __('Emergency recovery mode is now ENABLED. Every action is audited; disable it as soon as access is restored.')
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable) {
            $this->messageManager->addErrorMessage(
                (string) __('Emergency recovery mode could not be enabled. Please try again.')
            );
        }

        return $resultRedirect;
    }
}
