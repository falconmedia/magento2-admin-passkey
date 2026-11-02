<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Auth;

use Magento\Backend\Model\Auth\Session as AdminAuthSession;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Security\Model\AdminSessionsManager;
use Magento\User\Model\Backend\Config\ObserverConfig;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;

/**
 * Post-login bookkeeping that Magento performs for a password login.
 *
 * Mirrors, for the passkey flow:
 *  - {@see User::login()}: recordLogin() (logdate/lognum);
 *  - {@see \Magento\User\Observer\Backend\AuthObserver}: reset the native failure
 *    counter and flag an expired password (forced change is then enforced by
 *    {@see \Magento\User\Observer\Backend\ForceAdminPasswordChangeObserver});
 *  - {@see \Magento\Security\Model\Plugin\Auth::afterLogin()}: register the
 *    admin_user_session record and terminate other sessions when admin account
 *    sharing is disabled. Without this record Magento_Security ends the session on
 *    the next request and the session limits would be bypassed.
 *
 * Must run after {@see AdminAuthSession::processLogin()} (the new session id is
 * what Magento_Security stores).
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class NativeLoginRecorder
{
    private const SECURITY_MODULE = 'Magento_Security';

    public function __construct(
        private readonly UserResource $userResource,
        private readonly ObserverConfig $observerConfig,
        private readonly AdminAuthSession $authSession,
        private readonly BackendUrlInterface $backendUrl,
        private readonly MessageManagerInterface $messageManager,
        private readonly ModuleManager $moduleManager,
        private readonly AdminSessionsManager $adminSessionsManager
    ) {
    }

    /**
     * Record the login and register the Magento_Security admin session.
     *
     * @param User $user
     * @return void
     */
    public function afterLogin(User $user): void
    {
        $userId = (int) $user->getId();

        $this->userResource->unlock($userId);
        $this->userResource->recordLogin($user);
        $this->flagExpiredPassword($userId);

        if ($this->moduleManager->isEnabled(self::SECURITY_MODULE)) {
            $this->adminSessionsManager->processLogin();
            if ($this->adminSessionsManager->getCurrentSession()->isOtherSessionsTerminated()) {
                $this->messageManager->addWarningMessage(
                    __('All other open sessions for this account were terminated.')
                );
            }
        }
    }

    /**
     * Same notice + session flag as the native AuthObserver::_checkExpiredPassword().
     *
     * @param int $userId
     * @return void
     */
    private function flagExpiredPassword(int $userId): void
    {
        $latestPassword = $this->userResource->getLatestPassword($userId);
        if (!$latestPassword || !$this->observerConfig->_isLatestPasswordExpired($latestPassword)) {
            return;
        }

        if ($this->observerConfig->isPasswordChangeForced()) {
            $message = __('It\'s time to change your password.');
        } else {
            $message = __(
                'It\'s time to <a href="%1">change your password</a>.',
                $this->backendUrl->getUrl('adminhtml/system_account/')
            );
        }
        $this->messageManager->addNoticeMessage($message);
        $lastMessage = $this->messageManager->getMessages()->getLastAddedMessage();
        if ($lastMessage) {
            $lastMessage->setIdentifier('magento_user_password_expired')->setIsSticky(true);
            $this->authSession->setPciAdminUserIsPasswordExpired(true);
        }
    }
}
