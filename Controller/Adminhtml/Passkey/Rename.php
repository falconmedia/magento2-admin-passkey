<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Passkey;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Controller\Adminhtml\AbstractPasskeyJsonAction;
use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use FalconMedia\AdminPasskey\Model\Passkey\CredentialAccessValidator;
use FalconMedia\AdminPasskey\Model\Passkey\FriendlyNameNormalizer;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Authenticated endpoint to set or rename a passkey's friendly name.
 *
 * Requires an Admin session and the passkeys ACL resource. The credential must
 * belong to the current admin (verified via {@see CredentialAccessValidator}); an
 * admin can never rename another admin's credential here. Used by both the setup
 * wizard "name your passkey" step and the profile management page.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Rename extends AbstractPasskeyJsonAction implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this endpoint.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::passkeys';

    /**
     * Passkey management is personal self-service: the action only ever touches
     * the session admin's OWN credential (ownership is enforced in handle()), so
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
        private readonly CredentialAccessValidator $accessValidator,
        private readonly FriendlyNameNormalizer $friendlyNameNormalizer,
        private readonly AuditLoggerInterface $auditLogger
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

        try {
            $friendlyName = $this->friendlyNameNormalizer->normalize(
                (string) $this->getRequest()->getParam('friendly_name', '')
            );
        } catch (LocalizedException $e) {
            return $this->jsonPayload->error((string) $e->getMessage());
        }

        $credential->setFriendlyName($friendlyName);
        $this->credentialRepository->save($credential);
        $this->auditNameUpdate($credential);

        return $this->jsonPayload->success([
            'friendlyName' => $friendlyName,
            'message' => (string) __('Your passkey name has been updated.'),
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

    /**
     * Record the friendly-name update; audit failures must never break the flow.
     *
     * @param CredentialInterface $credential
     * @return void
     */
    private function auditNameUpdate(CredentialInterface $credential): void
    {
        try {
            $this->auditLogger->record(
                AuditLoggerInterface::EVENT_PASSKEY_NAME_UPDATED,
                [
                    AuditLoggerInterface::CONTEXT_TARGET => $credential->getAdminUserId(),
                    AuditLoggerInterface::CONTEXT_IP => $this->getRemoteIp(),
                    AuditLoggerInterface::CONTEXT_METADATA => [
                        'credential_row_id' => $credential->getId(),
                        'friendly_name' => $credential->getFriendlyName(),
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $this->appLogger->error(
                'Failed to record audit event for passkey name update: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
