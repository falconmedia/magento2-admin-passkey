<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Login;

use FalconMedia\AdminPasskey\Controller\Adminhtml\AbstractPasskeyJsonAction;
use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use FalconMedia\AdminPasskey\Model\Login\RateLimiterInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\AssertionChallengeServiceInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * Pre-auth passkey assertion options endpoint.
 *
 * Returns publicKeyCredentialRequestOptions for a discoverable-credential login
 * (no username supplied), so the response is identical for every visitor and
 * cannot be used to enumerate admins. Whitelisted for pre-auth access by
 * {@see \FalconMedia\AdminPasskey\Plugin\Backend\Authentication\AllowPasskeyLoginActions};
 * protected by the Admin form key and a per-IP rate limit.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Options extends AbstractPasskeyJsonAction implements HttpPostActionInterface
{
    /**
     * Rate-limit bucket prefix for this endpoint.
     */
    private const RATE_LIMIT_PREFIX = 'assert_options_';

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        RemoteAddress $remoteAddress,
        JsonPayloadFactory $jsonPayload,
        LoggerInterface $appLogger,
        private readonly AssertionChallengeServiceInterface $assertionChallengeService,
        private readonly RateLimiterInterface $rateLimiter
    ) {
        parent::__construct($context, $resultJsonFactory, $formKeyValidator, $remoteAddress, $jsonPayload, $appLogger);
    }

    /**
     * @inheritdoc
     */
    protected function handle(): array
    {
        $remoteIp = $this->getRemoteIp();
        $bucket = self::RATE_LIMIT_PREFIX . ($remoteIp ?? 'unknown');

        if ($this->rateLimiter->isLimited($bucket)) {
            return $this->jsonPayload->error((string) __('Too many attempts. Please wait and try again.'));
        }
        $this->rateLimiter->registerAttempt($bucket);

        $options = $this->assertionChallengeService->createOptions(null, $remoteIp);

        return $this->jsonPayload->success(['publicKey' => $options]);
    }

    /**
     * Pre-auth endpoint: access is controlled by the open-action whitelist plugin,
     * the form key and the rate limiter, not by an Admin ACL resource.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return true;
    }
}
