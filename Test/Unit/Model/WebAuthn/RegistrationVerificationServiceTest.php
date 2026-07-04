<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterfaceFactory;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\UserVerification;
use FalconMedia\AdminPasskey\Model\WebAuthn\AuthenticatorDataParser;
use FalconMedia\AdminPasskey\Model\WebAuthn\Asn1\DerEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\Base64UrlEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\ChallengeGuard;
use FalconMedia\AdminPasskey\Model\WebAuthn\ClientDataParser;
use FalconMedia\AdminPasskey\Model\WebAuthn\CoseKeyConverter;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use FalconMedia\AdminPasskey\Model\WebAuthn\RegistrationVerificationService;
use FalconMedia\AdminPasskey\Model\WebAuthn\RelyingPartyProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Deterministic security regression tests for registration (attestation)
 * verification. Real crypto/parse collaborators are used; only the persistence,
 * challenge and audit boundaries are mocked. No security check is stubbed.
 */
class RegistrationVerificationServiceTest extends TestCase
{
    private const CHALLENGE = 'reg-challenge-value';
    private const ADMIN_ID = 42;

    /** @var ChallengeGuard&MockObject */
    private ChallengeGuard $challengeGuard;

    /** @var RelyingPartyProvider&MockObject */
    private RelyingPartyProvider $relyingParty;

    /** @var ConfigProvider&MockObject */
    private ConfigProvider $configProvider;

    /** @var CredentialRepositoryInterface&MockObject */
    private CredentialRepositoryInterface $credentialRepository;

    /** @var AuditLoggerInterface&MockObject */
    private AuditLoggerInterface $auditLogger;

    private bool $challengeConsumed = false;
    private RegistrationVerificationService $service;
    private WebAuthnTestVectors $vectors;

    protected function setUp(): void
    {
        $this->vectors = new WebAuthnTestVectors('example.com', 'https://example.com', self::ADMIN_ID);

        $this->challengeGuard = $this->createMock(ChallengeGuard::class);
        $this->relyingParty = $this->createMock(RelyingPartyProvider::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->credentialRepository = $this->createMock(CredentialRepositoryInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $challenge = $this->createMock(ChallengeInterface::class);
        $challenge->method('getChallenge')->willReturn(self::CHALLENGE);
        $challenge->method('getAdminUserId')->willReturn(self::ADMIN_ID);
        $this->challengeGuard->method('loadPending')->willReturn($challenge);
        $this->challengeGuard->method('consume')->willReturnCallback(
            function () {
                $this->challengeConsumed = true;
            }
        );

        $this->relyingParty->method('getId')->willReturn('example.com');
        $this->relyingParty->method('getOrigin')->willReturn('https://example.com');
        $this->configProvider->method('getUserVerification')->willReturn(UserVerification::PREFERRED);

        $credentialFactory = $this->createMock(CredentialInterfaceFactory::class);
        $credentialFactory->method('create')->willReturnCallback(static fn () => new InMemoryCredential());

        $this->service = new RegistrationVerificationService(
            $this->challengeGuard,
            new ClientDataParser(new Json()),
            new AuthenticatorDataParser(),
            new CoseKeyConverter(new DerEncoder()),
            $this->relyingParty,
            $this->configProvider,
            $this->credentialRepository,
            $credentialFactory,
            new Base64UrlEncoder(),
            $this->auditLogger,
            new Json(),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testSuccessfulRegistrationPersistsCredentialAndConsumesChallengeFirst(): void
    {
        $this->credentialRepository->method('getByCredentialId')
            ->willThrowException(new NoSuchEntityException(__('none')));

        $saved = null;
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(
                function (CredentialInterface $credential) use (&$saved): CredentialInterface {
                    $this->assertTrue($this->challengeConsumed, 'Challenge must be consumed before persisting');
                    $credential->setId(101);
                    $saved = $credential;
                    return $credential;
                }
            );
        $this->auditLogger->expects($this->once())
            ->method('record')
            ->with(AuditLoggerInterface::EVENT_PASSKEY_REGISTRATION, $this->anything());

        $result = $this->service->verify(self::ADMIN_ID, $this->vectors->registration(self::CHALLENGE, ['signCount' => 5]));

        $this->assertSame(self::ADMIN_ID, $result->getAdminUserId());
        $this->assertSame($this->vectors->getCredentialId(), $result->getCredentialId());
        $this->assertSame(CredentialInterface::STATUS_ACTIVE, $result->getStatus());
        $this->assertSame($this->vectors->getEncodedCoseKey(), $result->getPublicKey());
        $this->assertSame(5, $result->getSignCount());
        $this->assertNotNull($saved);
    }

    public function testInvalidOriginRejected(): void
    {
        $this->allowChallengeAndNoDuplicate();

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey origin is not allowed.');
        $this->service->verify(self::ADMIN_ID, $this->vectors->registration(self::CHALLENGE, ['origin' => 'https://evil.example']));
    }

    public function testWrongCeremonyTypeRejected(): void
    {
        $this->allowChallengeAndNoDuplicate();

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('Unexpected passkey ceremony type.');
        $this->service->verify(self::ADMIN_ID, $this->vectors->registration(self::CHALLENGE, ['type' => 'webauthn.get']));
    }

    public function testRpIdMismatchRejected(): void
    {
        $this->allowChallengeAndNoDuplicate();

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey relying party could not be verified.');
        $this->service->verify(self::ADMIN_ID, $this->vectors->registration(self::CHALLENGE, ['rpId' => 'evil.example']));
    }

    public function testUserVerificationRequiredButAbsentRejected(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->method('getUserVerification')->willReturn(UserVerification::REQUIRED);
        $this->rebuildServiceWithConfig($configProvider);
        $this->allowChallengeAndNoDuplicate();

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('User verification is required but was not performed.');
        $this->service->verify(self::ADMIN_ID, $this->vectors->registration(self::CHALLENGE, ['uv' => false]));
    }

    public function testMissingAttestedCredentialDataRejected(): void
    {
        $this->allowChallengeAndNoDuplicate();

        $this->expectException(WebAuthnVerificationException::class);
        $this->service->verify(
            self::ADMIN_ID,
            $this->vectors->registration(self::CHALLENGE, ['omitAttestedData' => true])
        );
    }

    public function testDuplicateCredentialIdRejectedWithoutPersisting(): void
    {
        $this->credentialRepository->method('getByCredentialId')->willReturn(new InMemoryCredential());
        $this->credentialRepository->expects($this->never())->method('save');

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('This passkey is already registered.');
        $this->service->verify(self::ADMIN_ID, $this->vectors->registration(self::CHALLENGE));
    }

    /**
     * Allow the challenge and treat the credential id as not yet registered.
     *
     * @return void
     */
    private function allowChallengeAndNoDuplicate(): void
    {
        $this->credentialRepository->method('getByCredentialId')
            ->willThrowException(new NoSuchEntityException(__('none')));
    }

    /**
     * Rebuild the service with a different ConfigProvider (for UV policy tests).
     *
     * @param ConfigProvider $configProvider
     * @return void
     */
    private function rebuildServiceWithConfig(ConfigProvider $configProvider): void
    {
        $credentialFactory = $this->createMock(CredentialInterfaceFactory::class);
        $credentialFactory->method('create')->willReturnCallback(static fn () => new InMemoryCredential());

        $this->service = new RegistrationVerificationService(
            $this->challengeGuard,
            new ClientDataParser(new Json()),
            new AuthenticatorDataParser(),
            new CoseKeyConverter(new DerEncoder()),
            $this->relyingParty,
            $configProvider,
            $this->credentialRepository,
            $credentialFactory,
            new Base64UrlEncoder(),
            $this->auditLogger,
            new Json(),
            $this->createMock(LoggerInterface::class)
        );
    }
}
