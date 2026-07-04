<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\ResidentKey;
use FalconMedia\AdminPasskey\Model\Config\Source\UserVerification;
use FalconMedia\AdminPasskey\Model\WebAuthn\Base64UrlEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\ChallengeIssuer;
use FalconMedia\AdminPasskey\Model\WebAuthn\CredentialDescriptors;
use FalconMedia\AdminPasskey\Model\WebAuthn\RegistrationChallengeService;
use FalconMedia\AdminPasskey\Model\WebAuthn\RelyingPartyProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for publicKeyCredentialCreationOptions building.
 *
 * Persistence/config collaborators are mocked; the real Base64UrlEncoder verifies
 * user-handle base64url correctness.
 */
class RegistrationChallengeServiceTest extends TestCase
{
    /** @var ChallengeIssuer&MockObject */
    private ChallengeIssuer $challengeIssuer;

    /** @var RelyingPartyProvider&MockObject */
    private RelyingPartyProvider $relyingParty;

    /** @var CredentialDescriptors&MockObject */
    private CredentialDescriptors $credentialDescriptors;

    /** @var ConfigProvider&MockObject */
    private ConfigProvider $configProvider;

    /** @var UserFactory&MockObject */
    private UserFactory $userFactory;

    private RegistrationChallengeService $service;

    protected function setUp(): void
    {
        $this->challengeIssuer = $this->createMock(ChallengeIssuer::class);
        $this->relyingParty = $this->createMock(RelyingPartyProvider::class);
        $this->credentialDescriptors = $this->createMock(CredentialDescriptors::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->userFactory = $this->createMock(UserFactory::class);

        $this->service = new RegistrationChallengeService(
            $this->challengeIssuer,
            $this->relyingParty,
            $this->credentialDescriptors,
            $this->configProvider,
            new Base64UrlEncoder(),
            $this->userFactory
        );
    }

    public function testCreateOptionsBuildsFullCreationOptionsAndPersistsChallenge(): void
    {
        $this->stubUser(42, 'admin', 'Ada', 'Lovelace');

        $this->challengeIssuer->expects($this->once())
            ->method('issue')
            ->with(ChallengeInterface::TYPE_REGISTRATION, 42, '203.0.113.10')
            ->willReturn('REG_CHALLENGE');

        $this->relyingParty->method('getId')->willReturn('example.com');
        $this->relyingParty->method('getName')->willReturn('FalconMedia Admin Passkey');

        $this->credentialDescriptors->expects($this->once())
            ->method('forAdmin')
            ->with(42)
            ->willReturn([['type' => 'public-key', 'id' => 'existing-cred', 'transports' => ['internal']]]);

        $this->configProvider->method('getResidentKey')->willReturn(ResidentKey::PREFERRED);
        $this->configProvider->method('getUserVerification')->willReturn(UserVerification::PREFERRED);
        $this->configProvider->method('getCeremonyTimeoutMs')->willReturn(60000);

        $options = $this->service->createOptions(42, '203.0.113.10');

        // rp.
        $this->assertSame(['id' => 'example.com', 'name' => 'FalconMedia Admin Passkey'], $options['rp']);

        // user: id is base64url(admin user id), name/displayName from the admin user.
        $this->assertSame((new Base64UrlEncoder())->encode('42'), $options['user']['id']);
        $this->assertSame('admin', $options['user']['name']);
        $this->assertSame('Ada Lovelace', $options['user']['displayName']);

        // challenge.
        $this->assertSame('REG_CHALLENGE', $options['challenge']);

        // pubKeyCredParams: ES256 (-7) and RS256 (-257).
        $this->assertSame(
            [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            $options['pubKeyCredParams']
        );

        // authenticatorSelection.
        $this->assertSame(
            ['residentKey' => 'preferred', 'requireResidentKey' => false, 'userVerification' => 'preferred'],
            $options['authenticatorSelection']
        );

        // timeout + excludeCredentials.
        $this->assertSame(60000, $options['timeout']);
        $this->assertSame(
            [['type' => 'public-key', 'id' => 'existing-cred', 'transports' => ['internal']]],
            $options['excludeCredentials']
        );
    }

    public function testRequireResidentKeyTrueWhenResidentKeyRequired(): void
    {
        $this->stubUser(1, 'root', '', '');
        $this->challengeIssuer->method('issue')->willReturn('c');
        $this->relyingParty->method('getId')->willReturn('example.com');
        $this->relyingParty->method('getName')->willReturn('RP');
        $this->credentialDescriptors->method('forAdmin')->willReturn([]);
        $this->configProvider->method('getResidentKey')->willReturn(ResidentKey::REQUIRED);
        $this->configProvider->method('getUserVerification')->willReturn(UserVerification::REQUIRED);
        $this->configProvider->method('getCeremonyTimeoutMs')->willReturn(120000);

        $options = $this->service->createOptions(1);

        $this->assertTrue($options['authenticatorSelection']['requireResidentKey']);
        $this->assertSame('required', $options['authenticatorSelection']['residentKey']);
        // displayName falls back to the username when first/last name are empty.
        $this->assertSame('root', $options['user']['displayName']);
    }

    public function testCreateOptionsThrowsWhenAdminUserMissing(): void
    {
        $user = $this->createMock(User::class);
        $user->method('load')->willReturnSelf();
        $user->method('getId')->willReturn(null);
        $this->userFactory->method('create')->willReturn($user);

        $this->expectException(LocalizedException::class);

        $this->service->createOptions(999);
    }

    private function stubUser(int $id, string $username, string $firstName, string $lastName): void
    {
        $user = $this->createMock(User::class);
        $user->method('load')->with($id)->willReturnSelf();
        $user->method('getId')->willReturn($id);
        $user->method('getUserName')->willReturn($username);
        $user->method('getFirstName')->willReturn($firstName);
        $user->method('getLastName')->willReturn($lastName);
        $this->userFactory->method('create')->willReturn($user);
    }
}
