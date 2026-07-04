<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Passkey;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Controller\Adminhtml\AbstractPasskeyJsonAction;
use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use FalconMedia\AdminPasskey\Model\Passkey\CredentialAccessValidator;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Authenticated endpoint for an admin to revoke one of their own passkeys.
 *
 * Requires an Admin session and the passkeys ACL resource. The credential must
 * belong to the current admin (verified via {@see CredentialAccessValidator}).
 * The revoke itself is audited by the repository, so this endpoint does not audit
 * again to avoid a duplicate event.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Revoke extends AbstractPasskeyJsonAction implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this endpoint.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::passkeys';

    /**
     * Passkey management is personal self-service: revoke only ever acts on the
     * session admin's OWN credential (ownership is enforced in handle()), so
     * every authenticated admin is allowed — no extra ACL grant required.
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
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly CredentialAccessValidator $accessValidator
    ) {
        parent::__construct($context, $resultJsonFactory, $formKeyValidator, $remoteAddress, $jsonPayload, $appLogger);
    }

    /**
     * @inheritdoc
     */
    protected function handle(): array
    {
        $adminUserId = (int) $this->_auth->getUser()->getId();

        $credential = $this->resolveOwnedCredential($adminUserId);
        if ($credential === null) {
            return $this->jsonPayload->error((string) __('The passkey could not be found.'));
        }

        // The repository revoke records the passkey_revoke audit event.
        $this->credentialRepository->revoke((int) $credential->getId());

        return $this->jsonPayload->success([
            'message' => (string) __('Your passkey has been revoked.'),
        ]);
    }

    /**
     * Resolve the requested credential, but only when it belongs to the current admin.
     *
     * @param int $adminUserId
     * @return CredentialInterface|null
     */
    private function resolveOwnedCredential(int $adminUserId): ?CredentialInterface
    {
        $entityId = (int) $this->getRequest()->getParam('entity_id');
        $credentialId = (string) $this->getRequest()->getParam('credential_id', '');

        try {
            if ($entityId > 0) {
                $credential = $this->credentialRepository->getById($entityId);
            } elseif ($credentialId !== '') {
                $credential = $this->credentialRepository->getByCredentialId($credentialId);
            } else {
                return null;
            }
        } catch (NoSuchEntityException) {
            return null;
        }

        if (!$this->accessValidator->isOwnedByAdmin($credential->getAdminUserId(), $adminUserId)) {
            return null;
        }

        return $credential;
    }
}
