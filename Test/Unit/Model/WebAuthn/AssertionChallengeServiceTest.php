<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\UserVerification;
use FalconMedia\AdminPasskey\Model\WebAuthn\AssertionChallengeService;
use FalconMedia\AdminPasskey\Model\WebAuthn\ChallengeIssuer;
use FalconMedia\AdminPasskey\Model\WebAuthn\CredentialDescriptors;
use FalconMedia\AdminPasskey\Model\WebAuthn\RelyingPartyProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for publicKeyCredentialRequestOptions building (discoverable + known).
 */
class AssertionChallengeServiceTest extends TestCase
{
    /** @var ChallengeIssuer&MockObject */
    private ChallengeIssuer $challengeIssuer;

    /** @var RelyingPartyProvider&MockObject */
    private RelyingPartyProvider $relyingParty;

    /** @var CredentialDescriptors&MockObject */
    private CredentialDescriptors $credentialDescriptors;

    /** @var ConfigProvider&MockObject */
    private ConfigProvider $configProvider;

    private AssertionChallengeService $service;

    protected function setUp(): void
    {
        $this->challengeIssuer = $this->createMock(ChallengeIssuer::class);
        $this->relyingParty = $this->createMock(RelyingPartyProvider::class);
        $this->credentialDescriptors = $this->createMock(CredentialDescriptors::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->service = new AssertionChallengeService(
            $this->challengeIssuer,
            $this->relyingParty,
            $this->credentialDescriptors,
            $this->configProvider
        );
    }

    public function testDiscoverableCredentialsWhenNoAdminUser(): void
    {
        $this->challengeIssuer->expects($this->once())
            ->method('issue')
            ->with(ChallengeInterface::TYPE_ASSERTION, null, null)
            ->willReturn('ASSERT_CHALLENGE');
        $this->relyingParty->method('getId')->willReturn('example.com');
        $this->configProvider->method('getUserVerification')->willReturn(UserVerification::PREFERRED);
        $this->configProvider->method('getCeremonyTimeoutMs')->willReturn(60000);

        // No admin user -> allowCredentials must be empty and repository must not be queried.
        $this->credentialDescriptors->expects($this->never())->method('forAdmin');

        $options = $this->service->createOptions();

        $this->assertSame('ASSERT_CHALLENGE', $options['challenge']);
        $this->assertSame(60000, $options['timeout']);
        $this->assertSame('example.com', $options['rpId']);
        $this->assertSame('preferred', $options['userVerification']);
        $this->assertSame([], $options['allowCredentials']);
    }

    public function testKnownCredentialsWhenAdminUserProvided(): void
    {
        $this->challengeIssuer->expects($this->once())
            ->method('issue')
            ->with(ChallengeInterface::TYPE_ASSERTION, 42, '203.0.113.10')
            ->willReturn('ASSERT_CHALLENGE');
        $this->relyingParty->method('getId')->willReturn('example.com');
        $this->configProvider->method('getUserVerification')->willReturn(UserVerification::REQUIRED);
        $this->configProvider->method('getCeremonyTimeoutMs')->willReturn(90000);

        $this->credentialDescriptors->expects($this->once())
            ->method('forAdmin')
            ->with(42)
            ->willReturn([
                ['type' => 'public-key', 'id' => 'cred-a', 'transports' => ['internal']],
                ['type' => 'public-key', 'id' => 'cred-b'],
            ]);

        $options = $this->service->createOptions(42, '203.0.113.10');

        $this->assertSame('ASSERT_CHALLENGE', $options['challenge']);
        $this->assertSame(90000, $options['timeout']);
        $this->assertSame('required', $options['userVerification']);
        $this->assertCount(2, $options['allowCredentials']);
        $this->assertSame('cred-a', $options['allowCredentials'][0]['id']);
        $this->assertSame(['internal'], $options['allowCredentials'][0]['transports']);
        $this->assertSame('cred-b', $options['allowCredentials'][1]['id']);
    }
}
