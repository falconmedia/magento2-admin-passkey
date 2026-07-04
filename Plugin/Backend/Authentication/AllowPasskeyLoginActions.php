<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Plugin\Backend\Authentication;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Plugin\Authentication;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;

/**
 * Whitelists the pre-auth passkey login endpoints so they can run before an Admin
 * session exists.
 *
 * Magento gates every backend controller through the "open actions" list in
 * {@see \Magento\Backend\App\Action\Plugin\Authentication} (an around plugin on
 * {@see \Magento\Backend\App\AbstractAction::dispatch()}). Actions not on that
 * list are forwarded to the login page when the user is not authenticated — which
 * is exactly what would happen to the passkey assertion options/verify endpoints,
 * making passkey login impossible.
 *
 * The native open-actions list is a hardcoded protected property with no injection
 * seam, and {@see \Magento\Rss\App\Action\Plugin\BackendAuthentication} shows the
 * only supported extension point is that same plugin. Rather than replacing the
 * whole auth plugin (which would break if another module does the same), this
 * additively wraps its around method: for the explicitly configured full action
 * names it marks the request dispatched and proceeds straight to the controller,
 * bypassing the login redirect. Every other request keeps the native auth check.
 *
 * The whitelisted controllers still enforce their own protections (form-key CSRF,
 * per-IP rate limiting, WebAuthn verification), so opening the route does not
 * weaken security. Only the two pre-auth login endpoints are ever whitelisted;
 * the registration endpoints stay behind the normal Admin session + ACL check.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class AllowPasskeyLoginActions
{
    /**
     * @param string[] $openActions Full action names (routeId_controller_action) to open pre-auth.
     */
    public function __construct(
        private readonly array $openActions = []
    ) {
    }

    /**
     * Bypass the native login enforcement for the whitelisted pre-auth endpoints.
     *
     * @param Authentication $subject Native backend authentication plugin.
     * @param callable $proceed Native aroundDispatch invocation.
     * @param AbstractAction $action Dispatched backend controller.
     * @param \Closure $dispatchProceed Closure proceeding to AbstractAction::dispatch().
     * @param RequestInterface $request Current request.
     * @return mixed
     */
    public function aroundAroundDispatch(
        Authentication $subject,
        callable $proceed,
        AbstractAction $action,
        \Closure $dispatchProceed,
        RequestInterface $request
    ) {
        if ($this->isOpenAction($request)) {
            // Mirror the native open-action behaviour: mark dispatched and run the
            // controller directly, skipping the not-logged-in login redirect.
            $request->setDispatched(true);

            return $dispatchProceed($request);
        }

        return $proceed($action, $dispatchProceed, $request);
    }

    /**
     * Whether the request targets one of the configured pre-auth passkey actions.
     *
     * @param RequestInterface $request
     * @return bool
     */
    public function isOpenAction(RequestInterface $request): bool
    {
        if (!$request instanceof HttpRequest) {
            return false;
        }

        return in_array(strtolower($request->getFullActionName()), $this->openActions, true);
    }
}
