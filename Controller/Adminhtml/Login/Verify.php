<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Controller\Adminhtml\Login;

use FalconMedia\AdminPasskey\Controller\Adminhtml\AbstractPasskeyJsonAction;
use FalconMedia\AdminPasskey\Model\Auth\AdminSessionCreatorInterface;
use FalconMedia\AdminPasskey\Model\Endpoint\JsonPayloadFactory;
use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use FalconMedia\AdminPasskey\Model\Login\LoginAttemptRecorderInterface;
use FalconMedia\AdminPasskey\Model\Login\RateLimiterInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\AssertionVerificationServiceInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Pre-auth passkey assertion verify endpoint.
 *
 * Verifies the browser assertion via {@see AssertionVerificationServiceInterface}
 * (which consumes the single-use challenge and validates origin, rpId, signature
 * and sign-counter), then creates the native Admin session — regenerating the
 * session id — through {@see AdminSessionCreatorInterface}. All failures return the
 * same generic message so the endpoint cannot be used to enumerate admins or
 * probe credentials. The passkey path is already audited by the verification
 * service, so the recorder only feeds the rate-limit/lockout counter and never
 * re-audits the passkey outcome.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class Verify extends AbstractPasskeyJsonAction implements HttpPostActionInterface
{
    /**
     * Rate-limit bucket prefix for this endpoint.
     */
    private const RATE_LIMIT_PREFIX = 'assert_verify_';

    /**
     * Request param carrying the browser PublicKeyCredential assertion JSON.
     */
    private const PARAM_ASSERTION = 'assertion';

    /**
     * Request param carrying the optional post-login redirect URL.
     */
    private const PARAM_REDIRECT = 'redirect_url';

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FormKeyValidator $formKeyValidator,
        RemoteAddress $remoteAddress,
        JsonPayloadFactory $jsonPayload,
        LoggerInterface $appLogger,
        private readonly AssertionVerificationServiceInterface $assertionVerificationService,
        private readonly AdminSessionCreatorInterface $adminSessionCreator,
        private readonly LoginAttemptRecorderInterface $loginAttemptRecorder,
        private readonly RateLimiterInterface $rateLimiter,
        private readonly LockoutManagerInterface $lockoutManager,
        private readonly Json $json
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
        // Durable lockout blocks passkey login too (suppressed while recovery mode is active).
        if ($this->lockoutManager->isLocked(null, null, $remoteIp)) {
            return $this->jsonPayload->error(
                (string) __('Too many failed attempts. Please try again later.')
            );
        }
        $this->rateLimiter->registerAttempt($bucket);

        $assertion = $this->readAssertion();
        if ($assertion === null) {
            $this->loginAttemptRecorder->recordFailure(
                null,
                LoginAttemptRecorderInterface::METHOD_PASSKEY,
                'malformed_assertion',
                $remoteIp
            );

            return $this->genericFailure();
        }

        try {
            $result = $this->assertionVerificationService->verify($assertion, $remoteIp);
            $adminUserId = $result->getAdminUserId();
            $this->adminSessionCreator->login($adminUserId);
        } catch (WebAuthnVerificationException | AuthenticationException $e) {
            $this->loginAttemptRecorder->recordFailure(
                null,
                LoginAttemptRecorderInterface::METHOD_PASSKEY,
                'assertion_rejected',
                $remoteIp
            );

            return $this->genericFailure();
        }

        // Success: feed the counter (no re-audit — the passkey path is audited by
        // the verification service) and hand back a validated redirect target.
        $this->loginAttemptRecorder->recordSuccess(
            $adminUserId,
            LoginAttemptRecorderInterface::METHOD_PASSKEY,
            $remoteIp
        );
        $this->rateLimiter->reset($bucket);

        $redirectUrl = $this->adminSessionCreator->resolveRedirectUrl($this->getRequestedRedirect());

        return $this->jsonPayload->success(['redirectUrl' => $redirectUrl]);
    }

    /**
     * Decode the posted assertion JSON into an array, or null when malformed.
     *
     * @return array<string,mixed>|null
     */
    private function readAssertion(): ?array
    {
        $raw = $this->getRequest()->getParam(self::PARAM_ASSERTION);
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

    /**
     * Read the optional requested redirect URL from the request.
     *
     * @return string|null
     */
    private function getRequestedRedirect(): ?string
    {
        $redirect = $this->getRequest()->getParam(self::PARAM_REDIRECT);

        return is_string($redirect) && $redirect !== '' ? $redirect : null;
    }

    /**
     * Single generic failure envelope reused for every rejection (no enumeration).
     *
     * @return array<string,mixed>
     */
    private function genericFailure(): array
    {
        return $this->jsonPayload->error((string) __('The passkey login could not be completed.'));
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
