<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Register;

use FalconMedia\AdminPasskey\Controller\Adminhtml\AbstractPasskeyJsonAction;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use FalconMedia\AdminPasskey\Model\Passkey\RegistrationStepUp;
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
 * Step-up: the admin must re-enter their current password (param "password")
 * before options are issued; a session alone never suffices, so a hijacked
 * session or XSS cannot silently enrol an attacker passkey.
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
     * Request param carrying the admin's current password (step-up).
     */
    private const PARAM_PASSWORD = 'password';

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
        private readonly RegistrationChallengeServiceInterface $registrationChallengeService,
        private readonly ConfigProvider $configProvider,
        private readonly RegistrationStepUp $stepUp
    ) {
        parent::__construct($context, $resultJsonFactory, $formKeyValidator, $remoteAddress, $jsonPayload, $appLogger);
    }

    /**
     * @inheritdoc
     */
    protected function handle(): array
    {
        if (!$this->configProvider->isEnabled()) {
            return $this->jsonPayload->error((string) __('Passkeys are disabled.'));
        }

        $user = $this->_auth->getUser();
        $adminUserId = (int) $user->getId();

        $password = $this->getRequest()->getParam(self::PARAM_PASSWORD);
        if (!is_string($password) || $password === ''
            || !$this->stepUp->authenticate($user, $password, $this->getRemoteIp())
        ) {
            return $this->jsonPayload->error(
                (string) __('Please confirm your current password to register a passkey.')
            );
        }

        $options = $this->registrationChallengeService->createOptions($adminUserId, $this->getRemoteIp());

        return $this->jsonPayload->success(['publicKey' => $options]);
    }
}
