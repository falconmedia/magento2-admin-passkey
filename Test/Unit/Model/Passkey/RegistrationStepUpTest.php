<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Passkey;

use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use FalconMedia\AdminPasskey\Model\Passkey\RegistrationStepUp;
use Magento\Backend\Model\Auth\Session as AdminAuthSession;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the password step-up that guards passkey registration.
 */
class RegistrationStepUpTest extends TestCase
{
    private const NOW = 1_800_000_000;

    /** @var array<string, mixed> */
    private array $sessionData = [];

    private LockoutManagerInterface&MockObject $lockoutManager;

    private DateTime&MockObject $dateTime;

    private RegistrationStepUp $stepUp;

    protected function setUp(): void
    {
        $session = $this->getMockBuilder(AdminAuthSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['setData'])
            ->getMock();
        $session->method('setData')->willReturnCallback(function (string $key, $value) use ($session) {
            $this->sessionData[$key] = $value;
            return $session;
        });
        $session->method('getData')->willReturnCallback(fn ($key = '') => $this->sessionData[$key] ?? null);

        $this->lockoutManager = $this->createMock(LockoutManagerInterface::class);
        $this->dateTime = $this->createMock(DateTime::class);
        $this->dateTime->method('gmtTimestamp')->willReturnCallback(fn () => self::NOW);

        $this->stepUp = new RegistrationStepUp($session, $this->lockoutManager, $this->dateTime);
    }

    public function testCorrectPasswordGrantsStepUpForThatAdminOnly(): void
    {
        $this->assertTrue($this->stepUp->authenticate($this->user(5, true), 'secret', '10.0.0.1'));

        $this->assertTrue($this->stepUp->isGranted(5));
        $this->assertFalse($this->stepUp->isGranted(6));
    }

    public function testWrongPasswordIsCountedAndGrantsNothing(): void
    {
        $this->lockoutManager->expects($this->once())
            ->method('registerFailedAttempt')
            ->with(5, 'jane', '10.0.0.1', 'password');

        $this->assertFalse($this->stepUp->authenticate($this->user(5, false), 'wrong', '10.0.0.1'));
        $this->assertFalse($this->stepUp->isGranted(5));
    }

    public function testNativelyLockedUserIsRejectedWithoutModuleCount(): void
    {
        $user = $this->user(5, null);
        $user->method('performIdentityCheck')->willThrowException(new UserLockedException(__('locked')));
        $this->lockoutManager->expects($this->never())->method('registerFailedAttempt');

        $this->assertFalse($this->stepUp->authenticate($user, 'secret', null));
        $this->assertFalse($this->stepUp->isGranted(5));
    }

    public function testLockedAccountCannotStepUp(): void
    {
        $this->lockoutManager->method('isLocked')->with(5, null, null)->willReturn(true);

        $user = $this->user(5, true);
        $user->expects($this->never())->method('performIdentityCheck');

        $this->assertFalse($this->stepUp->authenticate($user, 'secret', null));
    }

    public function testEmptyPasswordIsRejectedWithoutCheck(): void
    {
        $user = $this->user(5, true);
        $user->expects($this->never())->method('performIdentityCheck');

        $this->assertFalse($this->stepUp->authenticate($user, '', null));
    }

    public function testRevokeAndExpiryEndTheGrant(): void
    {
        $this->stepUp->authenticate($this->user(5, true), 'secret', null);
        $this->stepUp->revoke();
        $this->assertFalse($this->stepUp->isGranted(5));

        $this->sessionData['adminpasskey_registration_step_up'] = [
            'admin_user_id' => 5,
            'expires_at' => self::NOW - 1,
        ];
        $this->assertFalse($this->stepUp->isGranted(5));
    }

    /**
     * @param int $id
     * @param bool|null $passwordValid Null leaves verifyIdentity() unstubbed.
     * @return User&MockObject
     */
    private function user(int $id, ?bool $passwordValid): User
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getUserName', 'performIdentityCheck'])
            ->getMock();
        $user->method('getId')->willReturn($id);
        $user->method('getUserName')->willReturn('jane');
        if ($passwordValid === true) {
            $user->method('performIdentityCheck')->willReturnSelf();
        } elseif ($passwordValid === false) {
            $user->method('performIdentityCheck')
                ->willThrowException(new AuthenticationException(__('invalid password')));
        }

        return $user;
    }
}
