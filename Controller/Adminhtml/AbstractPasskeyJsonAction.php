<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml;

use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Shared base for the module's JSON-only Admin endpoints.
 *
 * Centralises the response envelope, form-key CSRF handling and remote-IP
 * resolution so each concrete endpoint only implements its own {@see self::handle()}
 * logic. CSRF is validated explicitly against the Admin form key (not left to the
 * default XHR bypass) so both the pre-auth login endpoints and the authenticated
 * registration endpoints require a valid form key on every POST.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
abstract class AbstractPasskeyJsonAction extends Action implements CsrfAwareActionInterface
{
    public function __construct(
        Context $context,
        protected readonly JsonFactory $resultJsonFactory,
        private readonly FormKeyValidator $formKeyValidator,
        protected readonly RemoteAddress $remoteAddress,
        protected readonly JsonPayloadFactory $jsonPayload,
        protected readonly LoggerInterface $appLogger
    ) {
        parent::__construct($context);
    }

    /**
     * Serialize the concrete endpoint result as JSON, converting any unexpected
     * error into a generic, non-enumerating failure response.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            return $result->setData($this->handle());
        } catch (\Throwable $e) {
            $this->appLogger->warning('AdminPasskey endpoint error: ' . $e->getMessage());
            $result->setHttpResponseCode(400);

            return $result->setData(
                $this->jsonPayload->error((string) __('The passkey request could not be completed.'))
            );
        }
    }

    /**
     * Produce the JSON payload for this endpoint.
     *
     * @return array<string,mixed>
     */
    abstract protected function handle(): array;

    /**
     * @inheritdoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode(403);
        $result->setData($this->jsonPayload->error((string) __('Invalid Form Key. Please refresh the page.')));

        return new InvalidRequestException($result, [__('Invalid Form Key. Please refresh the page.')]);
    }

    /**
     * @inheritdoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $this->formKeyValidator->validate($request);
    }

    /**
     * Resolve the caller's remote IP, or null when it cannot be determined.
     *
     * @return string|null
     */
    protected function getRemoteIp(): ?string
    {
        $ip = $this->remoteAddress->getRemoteAddress();

        return is_string($ip) && $ip !== '' ? $ip : null;
    }
}
