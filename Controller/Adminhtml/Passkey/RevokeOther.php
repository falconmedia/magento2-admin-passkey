<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Passkey;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Controller\Adminhtml\AbstractPasskeyJsonAction;
use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Super-admin endpoint to revoke another administrator's passkey.
 *
 * Gated by the stricter passkeys_manage_others ACL resource (separate from the
 * self-service passkeys resource), so only explicitly authorised super-admins can
 * revoke credentials they do not own. The revoke is audited by the repository with
 * the acting admin (resolved from the session) as actor, the credential owner as
 * target, and the credential row id in metadata. Confirmation is handled in the UI
 * before the POST is sent.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class RevokeOther extends AbstractPasskeyJsonAction implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this endpoint.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::passkeys_manage_others';

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        RemoteAddress $remoteAddress,
        JsonPayloadFactory $jsonPayload,
        LoggerInterface $appLogger,
        private readonly CredentialRepositoryInterface $credentialRepository
    ) {
        parent::__construct($context, $resultJsonFactory, $formKeyValidator, $remoteAddress, $jsonPayload, $appLogger);
    }

    /**
     * @inheritdoc
     */
    protected function handle(): array
    {
        $entityId = (int) $this->getRequest()->getParam('entity_id');
        if ($entityId <= 0) {
            return $this->jsonPayload->error((string) __('The passkey could not be found.'));
        }

        try {
            $credential = $this->credentialRepository->getById($entityId);
        } catch (NoSuchEntityException) {
            return $this->jsonPayload->error((string) __('The passkey could not be found.'));
        }

        // The repository revoke records the passkey_revoke audit event with the
        // current admin as actor and the credential owner as target.
        $this->credentialRepository->revoke((int) $credential->getId());

        return $this->jsonPayload->success([
            'targetAdminUserId' => $credential->getAdminUserId(),
            'message' => (string) __('The passkey has been revoked.'),
        ]);
    }
}
