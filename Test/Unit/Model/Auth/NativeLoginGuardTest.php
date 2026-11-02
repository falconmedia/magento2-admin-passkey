<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Auth;

use FalconMedia\AdminPasskey\Model\Auth\NativeLoginGuard;
use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Security\Model\UserExpirationManager;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit coverage for the native account checks re-applied to passkey login.
 */
class NativeLoginGuardTest extends TestCase
{
    private const NOW = '2026-11-02 12:00:00';

    private UserExpirationManager&MockObject $expirationManager;

    private LockoutManagerInterface&MockObject $lockoutManager;

    private ModuleManager&MockObject $moduleManager;

    private NativeLoginGuard $guard;

    protected function setUp(): void
    {
        $this->moduleManager = $this->createMock(ModuleManager::class);
        $this->moduleManager->method('isEnabled')->willReturn(true);
        $this->expirationManager = $this->createMock(UserExpirationManager::class);
        $this->lockoutManager = $this->createMock(LockoutManagerInterface::class);
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn((int) strtotime(self::NOW . ' UTC'));

        $this->guard = new NativeLoginGuard(
            $this->moduleManager,
            $this->expirationManager,
            $this->lockoutManager,
            $dateTime,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testHealthyUserPasses(): void
    {
        $this->guard->assertCanLogin($this->user());
        $this->addToAssertionCount(1);
    }

    public function testUserWithoutRoleIsRejected(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->guard->assertCanLogin($this->user(hasRole: false));
    }

    public function testNativelyLockedUserIsRejected(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->guard->assertCanLogin($this->user(lockExpires: '2026-11-02 12:30:00'));
    }

    public function testExpiredNativeLockIsIgnored(): void
    {
        $this->guard->assertCanLogin($this->user(lockExpires: '2026-11-02 11:00:00'));
        $this->addToAssertionCount(1);
    }

    public function testExpiredUserIsDeactivatedAndRejected(): void
    {
        $this->expirationManager->method('isUserExpired')->with('7')->willReturn(true);
        $this->expirationManager->expects($this->once())->method('deactivateExpiredUsersById')->with([7]);

        $this->expectException(AuthenticationException::class);
        $this->guard->assertCanLogin($this->user());
    }

    public function testExpirationIsSkippedWhenSecurityModuleIsDisabled(): void
    {
        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturn(false);
        $this->expirationManager->expects($this->never())->method('isUserExpired');

        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn((int) strtotime(self::NOW . ' UTC'));
        $guard = new NativeLoginGuard(
            $moduleManager,
            $this->expirationManager,
            $this->lockoutManager,
            $dateTime,
            $this->createMock(LoggerInterface::class)
        );

        $guard->assertCanLogin($this->user());
    }

    public function testAccountLockoutIsRejectedButUsernameLockoutIsNotConsulted(): void
    {
        $this->lockoutManager->expects($this->once())
            ->method('isLocked')
            ->with(7, null, null)
            ->willReturn(true);

        $this->expectException(AuthenticationException::class);
        $this->guard->assertCanLogin($this->user());
    }

    /**
     * @return User&MockObject
     */
    private function user(bool $hasRole = true, ?string $lockExpires = null): User
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'hasAssigned2Role'])
            ->addMethods(['getLockExpires'])
            ->getMock();
        $user->method('getId')->willReturn(7);
        $user->method('hasAssigned2Role')->willReturn($hasRole ? [['role_id' => 1]] : false);
        $user->method('getLockExpires')->willReturn($lockExpires);

        return $user;
    }
}
