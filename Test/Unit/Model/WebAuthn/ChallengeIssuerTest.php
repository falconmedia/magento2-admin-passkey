<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterfaceFactory;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\WebAuthn\Base64UrlEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\ChallengeIssuer;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the secure, persisted challenge issuer.
 *
 * The repository and ConfigProvider are mocked (framework/persistence boundary);
 * the real Base64UrlEncoder is used so base64url correctness is genuinely asserted.
 */
class ChallengeIssuerTest extends TestCase
{
    /** @var ChallengeRepositoryInterface&MockObject */
    private ChallengeRepositoryInterface $challengeRepository;

    /** @var ChallengeInterfaceFactory&MockObject */
    private ChallengeInterfaceFactory $challengeFactory;

    /** @var ConfigProvider&MockObject */
    private ConfigProvider $configProvider;

    /** @var DateTime&MockObject */
    private DateTime $dateTime;

    private ChallengeIssuer $issuer;

    protected function setUp(): void
    {
        $this->challengeRepository = $this->createMock(ChallengeRepositoryInterface::class);
        $this->challengeFactory = $this->createMock(ChallengeInterfaceFactory::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->dateTime = $this->createMock(DateTime::class);

        $this->issuer = new ChallengeIssuer(
            $this->challengeRepository,
            $this->challengeFactory,
            $this->configProvider,
            new Base64UrlEncoder(),
            $this->dateTime
        );
    }

    public function testIssuePersistsExpiringSingleUseChallenge(): void
    {
        $captured = [];
        $challenge = $this->createMock(ChallengeInterface::class);
        $challenge->method('setAdminUserId')->willReturnCallback(
            function (?int $value) use ($challenge, &$captured): ChallengeInterface {
                $captured['admin_user_id'] = $value;
                return $challenge;
            }
        );
        $challenge->method('setChallenge')->willReturnCallback(
            function (?string $value) use ($challenge, &$captured): ChallengeInterface {
                $captured['challenge'] = $value;
                return $challenge;
            }
        );
        $challenge->method('setChallengeType')->willReturnCallback(
            function (?string $value) use ($challenge, &$captured): ChallengeInterface {
                $captured['type'] = $value;
                return $challenge;
            }
        );
        $challenge->method('setStatus')->willReturnCallback(
            function (?string $value) use ($challenge, &$captured): ChallengeInterface {
                $captured['status'] = $value;
                return $challenge;
            }
        );
        $challenge->method('setRemoteIp')->willReturnCallback(
            function (?string $value) use ($challenge, &$captured): ChallengeInterface {
                $captured['remote_ip'] = $value;
                return $challenge;
            }
        );
        $challenge->method('setExpiresAt')->willReturnCallback(
            function (?string $value) use ($challenge, &$captured): ChallengeInterface {
                $captured['expires_at'] = $value;
                return $challenge;
            }
        );

        $this->challengeFactory->method('create')->willReturn($challenge);
        $this->configProvider->method('getChallengeLifetimeSeconds')->willReturn(300);
        $this->dateTime->method('gmtTimestamp')->willReturn(1_000_000_000);
        $this->dateTime->expects($this->once())
            ->method('gmtDate')
            ->with('Y-m-d H:i:s', 1_000_000_300)
            ->willReturn('2001-09-09 01:51:40');

        $this->challengeRepository->expects($this->once())
            ->method('save')
            ->with($challenge)
            ->willReturn($challenge);

        $result = $this->issuer->issue(ChallengeInterface::TYPE_ASSERTION, 42, '203.0.113.10');

        $this->assertSame($result, $captured['challenge']);
        $this->assertSame(ChallengeInterface::TYPE_ASSERTION, $captured['type']);
        $this->assertSame(42, $captured['admin_user_id']);
        $this->assertSame(ChallengeInterface::STATUS_PENDING, $captured['status']);
        $this->assertSame('203.0.113.10', $captured['remote_ip']);
        $this->assertSame('2001-09-09 01:51:40', $captured['expires_at']);

        // The challenge is url-safe, unpadded, and decodes to 32 random bytes.
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $result);
        $this->assertSame(32, strlen((new Base64UrlEncoder())->decode($result)));
    }

    public function testIssueAllowsNullAdminUserForDiscoverableAssertion(): void
    {
        $captured = [];
        $challenge = $this->createMock(ChallengeInterface::class);
        foreach (['setChallenge', 'setChallengeType', 'setStatus', 'setRemoteIp', 'setExpiresAt'] as $setter) {
            $challenge->method($setter)->willReturn($challenge);
        }
        $challenge->method('setAdminUserId')->willReturnCallback(
            function (?int $value) use ($challenge, &$captured): ChallengeInterface {
                $captured['admin_user_id'] = $value;
                return $challenge;
            }
        );

        $this->challengeFactory->method('create')->willReturn($challenge);
        $this->configProvider->method('getChallengeLifetimeSeconds')->willReturn(300);
        $this->dateTime->method('gmtTimestamp')->willReturn(1_000_000_000);
        $this->dateTime->method('gmtDate')->willReturn('2001-09-09 01:51:40');
        $this->challengeRepository->method('save')->willReturn($challenge);

        $this->issuer->issue(ChallengeInterface::TYPE_ASSERTION, null, null);

        $this->assertNull($captured['admin_user_id']);
    }
}
