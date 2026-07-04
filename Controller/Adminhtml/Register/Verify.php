<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Register;

use FalconMedia\AdminPasskey\Controller\Adminhtml\AbstractPasskeyJsonAction;
use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use FalconMedia\AdminPasskey\Model\WebAuthn\RegistrationVerificationServiceInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Authenticated passkey registration verify endpoint.
 *
 * Requires an Admin session and the passkeys ACL resource. Verifies the browser
 * attestation via {@see RegistrationVerificationServiceInterface} and persists the
 * credential for the currently authenticated admin only (the service also audits
 * the outcome). Reused by the Step 12 setup wizard and profile management UI.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Verify extends AbstractPasskeyJsonAction implements HttpPostActionInterface
{
    /**
     * ACL resource protecting this endpoint.
     */
    public const ADMIN_RESOURCE = 'FalconMedia_AdminPasskey::passkeys';

    /**
     * Passkey enrolment is personal self-service: the credential is registered
     * only against the session admin's OWN account, so every authenticated admin
     * is allowed — no extra ACL grant required to enrol your own key.
     */
    protected function _isAllowed(): bool
    {
        return true;
    }

    /**
     * Request param carrying the browser PublicKeyCredential attestation JSON.
     */
    private const PARAM_CREDENTIAL = 'credential';

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        RemoteAddress $remoteAddress,
        JsonPayloadFactory $jsonPayload,
        LoggerInterface $appLogger,
        private readonly RegistrationVerificationServiceInterface $registrationVerificationService,
        private readonly Json $json
    ) {
        parent::__construct($context, $resultJsonFactory, $formKeyValidator, $remoteAddress, $jsonPayload, $appLogger);
    }

    /**
     * @inheritdoc
     */
    protected function handle(): array
    {
        $adminUserId = (int) $this->_auth->getUser()->getId();

        $attestation = $this->readCredential();
        if ($attestation === null) {
            return $this->jsonPayload->error((string) __('The passkey response is incomplete.'));
        }

        try {
            $credential = $this->registrationVerificationService->verify(
                $adminUserId,
                $attestation,
                $this->getRemoteIp()
            );
        } catch (WebAuthnVerificationException $e) {
            // The admin is acting on their own account, so the specific,
            // non-sensitive verification message is safe to surface here.
            return $this->jsonPayload->error((string) $e->getMessage());
        }

        return $this->jsonPayload->success([
            'credentialId' => $credential->getCredentialId(),
            'message' => (string) __('Your passkey has been registered.'),
        ]);
    }

    /**
     * Decode the posted attestation JSON into an array, or null when malformed.
     *
     * @return array<string,mixed>|null
     */
    private function readCredential(): ?array
    {
        $raw = $this->getRequest()->getParam(self::PARAM_CREDENTIAL);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        try {
            $decoded = $this->json->unserialize($raw);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
