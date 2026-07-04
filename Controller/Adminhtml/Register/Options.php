<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Register;

use FalconMedia\AdminPasskey\Controller\Adminhtml\AbstractPasskeyJsonAction;
use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use FalconMedia\AdminPasskey\Model\WebAuthn\RegistrationChallengeServiceInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Authenticated passkey registration options endpoint.
 *
 * Requires an Admin session and the passkeys ACL resource. Returns
 * publicKeyCredentialCreationOptions for the currently authenticated admin only,
 * so an admin can never request registration options for another account. Reused
 * by the Step 12 setup wizard and profile management UI.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Options extends AbstractPasskeyJsonAction implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this endpoint.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::passkeys';

    /**
     * Passkey enrolment is personal self-service: the ceremony options are issued
     * only for the session admin's OWN account, so every authenticated admin is
     * allowed — no extra ACL grant required to enrol your own key.
     */
    protected function _isAllowed(): bool
    {
        return true;
    }

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        RemoteAddress $remoteAddress,
        JsonPayloadFactory $jsonPayload,
        LoggerInterface $appLogger,
        private readonly RegistrationChallengeServiceInterface $registrationChallengeService
    ) {
        parent::__construct($context, $resultJsonFactory, $formKeyValidator, $remoteAddress, $jsonPayload, $appLogger);
    }

    /**
     * @inheritdoc
     */
    protected function handle(): array
    {
        $adminUserId = (int) $this->_auth->getUser()->getId();
        $options = $this->registrationChallengeService->createOptions($adminUserId, $this->getRemoteIp());

        return $this->jsonPayload->success(['publicKey' => $options]);
    }
}
