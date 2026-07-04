<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Auth;

use FalconMedia\AdminPasskey\Model\Auth\AdminSessionCreator;
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
 * Unit coverage for passkey session creation and Magento 2FA bypass.
 */
class AdminSessionCreatorTest extends TestCase
{
    private AdminAuthSession&MockObject $authSession;

    private UserFactory&MockObject $userFactory;

    private EventManagerInterface&MockObject $eventManager;

    private BackendUrlInterface&MockObject $backendUrl;

    private TfaInterface&MockObject $tfa;

    private TfaSessionInterface&MockObject $tfaSession;

    private AdminSessionCreator $adminSessionCreator;

    protected function setUp(): void
    {
        $this->authSession = $this->getMockBuilder(AdminAuthSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['setUser'])
            ->onlyMethods(['processLogin'])
            ->getMock();
        $this->userFactory = $this->createMock(UserFactory::class);
        $this->eventManager = $this->createMock(EventManagerInterface::class);
        $this->backendUrl = $this->createMock(BackendUrlInterface::class);
        $this->tfa = $this->createMock(TfaInterface::class);
        $this->tfaSession = $this->createMock(TfaSessionInterface::class);

        $this->adminSessionCreator = new AdminSessionCreator(
            $this->authSession,
            $this->userFactory,
            $this->eventManager,
            $this->backendUrl,
            $this->tfa,
            $this->tfaSession
        );
    }

    public function testLoginGrantsTfaAccessWhenTwoFactorAuthIsEnabled(): void
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

        $this->tfa->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->tfaSession->expects($this->once())->method('grantAccess');

        $this->eventManager->expects($this->once())->method('dispatch')
            ->with('backend_auth_user_login_success', ['user' => $user]);

        $this->adminSessionCreator->login(42);
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

        $this->adminSessionCreator->login(7);
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
        $this->tfaSession->expects($this->never())->method('grantAccess');

        $this->expectException(AuthenticationException::class);

        $this->adminSessionCreator->login(99);
    }
}
