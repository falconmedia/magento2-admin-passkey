<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Auth;

use FalconMedia\AdminPasskey\Model\Auth\AdminSessionCreator;
use FalconMedia\AdminPasskey\Model\Auth\NativeLoginGuard;
use FalconMedia\AdminPasskey\Model\Auth\NativeLoginRecorder;
use Magento\Backend\Model\Auth\Session as AdminAuthSession;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\TwoFactorAuth\Api\TfaInterface;
use Magento\TwoFactorAuth\Api\TfaSessionInterface;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for passkey session creation, native auth checks and the UV-gated 2FA bypass.
 */
class AdminSessionCreatorTest extends TestCase
{
    private AdminAuthSession&MockObject $authSession;

    private UserFactory&MockObject $userFactory;

    private EventManagerInterface&MockObject $eventManager;

    private BackendUrlInterface&MockObject $backendUrl;

    private TfaInterface&MockObject $tfa;

    private TfaSessionInterface&MockObject $tfaSession;

    private NativeLoginGuard&MockObject $loginGuard;

    private NativeLoginRecorder&MockObject $loginRecorder;

    private AdminSessionCreator $adminSessionCreator;

    protected function setUp(): void
    {
        $this->authSession = $this->getMockBuilder(AdminAuthSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['setUser'])
            ->onlyMethods(['processLogin', 'processLogout'])
            ->getMock();
        $this->userFactory = $this->createMock(UserFactory::class);
        $this->eventManager = $this->createMock(EventManagerInterface::class);
        $this->backendUrl = $this->createMock(BackendUrlInterface::class);
        $this->tfa = $this->createMock(TfaInterface::class);
        $this->tfaSession = $this->createMock(TfaSessionInterface::class);
        $this->loginGuard = $this->createMock(NativeLoginGuard::class);
        $this->loginRecorder = $this->createMock(NativeLoginRecorder::class);

        $this->adminSessionCreator = new AdminSessionCreator(
            $this->authSession,
            $this->userFactory,
            $this->eventManager,
            $this->backendUrl,
            $this->tfa,
            $this->tfaSession,
            $this->loginGuard,
            $this->loginRecorder
        );
    }

    public function testUserVerifiedLoginGrantsTfaAccessWhenTwoFactorAuthIsEnabled(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getIsActive', 'load'])
            ->getMock();
        $user->method('getId')->willReturn(42);
        $user->method('getIsActive')->willReturn(1);

        $this->userFactory->method('create')->willReturn($user);
        $user->expects($this->once())->method('load')->with(42)->willReturnSelf();

        $this->authSession->expects($this->once())->method('setUser')->with($user);
        $this->authSession->expects($this->once())->method('processLogin');

        $this->loginGuard->expects($this->once())->method('assertCanLogin')->with($user);
        $this->loginRecorder->expects($this->once())->method('afterLogin')->with($user);

        $this->tfa->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->tfaSession->expects($this->once())->method('grantAccess');

        $this->eventManager->expects($this->once())->method('dispatch')
            ->with('backend_auth_user_login_success', ['user' => $user]);

        $this->adminSessionCreator->login(42, true);
    }

    public function testLoginWithoutUserVerificationKeepsMagentoTwoFactorAuth(): void
    {
        $user = $this->activeUser(42);

        $this->authSession->expects($this->once())->method('processLogin');
        $this->tfaSession->expects($this->never())->method('grantAccess');
        $this->eventManager->expects($this->once())->method('dispatch')
            ->with('backend_auth_user_login_success', ['user' => $user]);

        $this->adminSessionCreator->login(42, false);
    }

    public function testGuardRejectionPreventsSession(): void
    {
        $this->activeUser(42);
        $this->loginGuard->method('assertCanLogin')
            ->willThrowException(new AuthenticationException(__('locked')));

        $this->authSession->expects($this->never())->method('setUser');
        $this->authSession->expects($this->never())->method('processLogin');
        $this->tfaSession->expects($this->never())->method('grantAccess');
        $this->eventManager->expects($this->never())->method('dispatch');

        $this->expectException(AuthenticationException::class);
        $this->adminSessionCreator->login(42, true);
    }

    public function testRecorderFailureLogsTheSessionOut(): void
    {
        $this->activeUser(42);
        $this->loginRecorder->method('afterLogin')->willThrowException(new \RuntimeException('db down'));

        $this->authSession->expects($this->once())->method('processLogin');
        $this->authSession->expects($this->once())->method('processLogout');
        $this->tfaSession->expects($this->never())->method('grantAccess');
        $this->eventManager->expects($this->never())->method('dispatch');

        $this->expectException(AuthenticationException::class);
        $this->adminSessionCreator->login(42, true);
    }

    /**
     * Wire the user factory to return an active user with the given id.
     *
     * @param int $id
     * @return User&MockObject
     */
    private function activeUser(int $id): User
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getIsActive', 'load'])
            ->getMock();
        $user->method('getId')->willReturn($id);
        $user->method('getIsActive')->willReturn(1);
        $user->method('load')->willReturnSelf();
        $this->userFactory->method('create')->willReturn($user);

        return $user;
    }

    public function testLoginDoesNotGrantTfaAccessWhenTwoFactorAuthIsDisabled(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getIsActive', 'load'])
            ->getMock();
        $user->method('getId')->willReturn(7);
        $user->method('getIsActive')->willReturn(1);

        $this->userFactory->method('create')->willReturn($user);
        $user->expects($this->once())->method('load')->with(7)->willReturnSelf();

        $this->authSession->expects($this->once())->method('setUser')->with($user);
        $this->authSession->expects($this->once())->method('processLogin');

        $this->tfa->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->tfaSession->expects($this->never())->method('grantAccess');

        $this->eventManager->expects($this->once())->method('dispatch')
            ->with('backend_auth_user_login_success', ['user' => $user]);

        $this->adminSessionCreator->login(7, true);
    }

    public function testLoginRejectsInactiveUser(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getIsActive', 'load'])
            ->getMock();
        $user->method('getId')->willReturn(null);
        $user->method('getIsActive')->willReturn(0);

        $this->userFactory->method('create')->willReturn($user);
        $user->expects($this->once())->method('load')->with(99)->willReturnSelf();

        $this->authSession->expects($this->never())->method('setUser');
        $this->loginGuard->expects($this->never())->method('assertCanLogin');
        $this->tfaSession->expects($this->never())->method('grantAccess');

        $this->expectException(AuthenticationException::class);

        $this->adminSessionCreator->login(99, true);
    }
}
