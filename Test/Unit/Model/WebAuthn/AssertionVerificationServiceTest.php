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
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Config\Source\UserVerification;
use FalconMedia\AdminPasskey\Model\WebAuthn\Asn1\DerEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\AssertionVerificationService;
use FalconMedia\AdminPasskey\Model\WebAuthn\AuthenticatorDataParser;
use FalconMedia\AdminPasskey\Model\WebAuthn\Base64UrlEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\ChallengeGuard;
use FalconMedia\AdminPasskey\Model\WebAuthn\ClientDataParser;
use FalconMedia\AdminPasskey\Model\WebAuthn\CoseKeyConverter;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use FalconMedia\AdminPasskey\Model\WebAuthn\SignatureVerifier;
use FalconMedia\AdminPasskey\Model\WebAuthn\RelyingPartyProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Deterministic security regression tests for assertion (authentication)
 * verification. Real crypto/parse collaborators are used; only the persistence,
 * challenge and audit boundaries are mocked. No security check is stubbed.
 */
class AssertionVerificationServiceTest extends TestCase
{
    private const CHALLENGE = 'assert-challenge-value';
    private const ADMIN_ID = 42;

    /** @var ChallengeGuard&MockObject */
    private ChallengeGuard $challengeGuard;

    /** @var ConfigProvider&MockObject */
    private ConfigProvider $configProvider;

    /** @var CredentialRepositoryInterface&MockObject */
    private CredentialRepositoryInterface $credentialRepository;

    /** @var AuditLoggerInterface&MockObject */
    private AuditLoggerInterface $auditLogger;

    private bool $challengeConsumed = false;
    private ?int $challengeAdminUserId = null;
    private AssertionVerificationService $service;
    private WebAuthnTestVectors $vectors;

    protected function setUp(): void
    {
        $this->vectors = new WebAuthnTestVectors('example.com', 'https://example.com', self::ADMIN_ID);
        $this->buildService($this->makeConfig(UserVerification::PREFERRED));
    }

    public function testSuccessfulAssertionUpdatesCredentialAndReturnsResult(): void
    {
        $credential = $this->activeCredential(1);
        $this->credentialRepository->method('getByCredentialId')->willReturn($credential);

        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(
                function (CredentialInterface $saved): CredentialInterface {
                    $this->assertTrue($this->challengeConsumed, 'Challenge must be consumed before verifying signature');
                    return $saved;
                }
            );
        $this->auditLogger->expects($this->once())
            ->method('record')
            ->with(AuditLoggerInterface::EVENT_PASSKEY_LOGIN, $this->anything());

        $result = $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['signCount' => 2]));

        $this->assertTrue($result->isVerified());
        $this->assertSame(self::ADMIN_ID, $result->getAdminUserId());
        $this->assertSame($this->vectors->getCredentialId(), $result->getCredentialId());
        $this->assertSame(2, $credential->getSignCount(), 'Sign count must be updated on success');
        $this->assertNotNull($credential->getLastUsedAt(), 'last_used_at must be updated on success');
    }

    public function testUnknownCredentialRejected(): void
    {
        $this->credentialRepository->method('getByCredentialId')
            ->willThrowException(new NoSuchEntityException(__('none')));

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey credential is not recognised.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE));
    }

    public function testRevokedCredentialRejected(): void
    {
        $credential = $this->activeCredential(1);
        $credential->setStatus(CredentialInterface::STATUS_REVOKED);
        $this->credentialRepository->method('getByCredentialId')->willReturn($credential);

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey credential is no longer active.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE));
    }

    public function testInvalidOriginRejected(): void
    {
        $this->credentialRepository->method('getByCredentialId')->willReturn($this->activeCredential(1));

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey origin is not allowed.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['origin' => 'https://evil.example']));
    }

    public function testRpIdMismatchRejected(): void
    {
        $this->credentialRepository->method('getByCredentialId')->willReturn($this->activeCredential(1));

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey relying party could not be verified.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['rpId' => 'evil.example']));
    }

    public function testInvalidSignatureRejectedAfterChallengeConsumed(): void
    {
        $this->credentialRepository->method('getByCredentialId')->willReturn($this->activeCredential(1));
        $this->credentialRepository->expects($this->never())->method('save');

        try {
            $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['tamperSignature' => true]));
            $this->fail('Expected a verification exception for a tampered signature');
        } catch (WebAuthnVerificationException $e) {
            $this->assertSame('The passkey signature is invalid.', $e->getMessage());
        }

        $this->assertTrue($this->challengeConsumed, 'Challenge must be consumed even when the signature fails (anti-replay)');
    }

    public function testWrongKeySignatureRejected(): void
    {
        $this->credentialRepository->method('getByCredentialId')->willReturn($this->activeCredential(1));

        $otherKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey signature is invalid.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['signWith' => $otherKey]));
    }

    public function testSignCountRegressionRejected(): void
    {
        // Stored sign count 5, new assertion reports 3 -> clone/replay.
        $this->credentialRepository->method('getByCredentialId')->willReturn($this->activeCredential(5));

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey sign counter is invalid.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['signCount' => 3]));
    }

    public function testEqualSignCountRejectedUnlessBothZero(): void
    {
        $this->credentialRepository->method('getByCredentialId')->willReturn($this->activeCredential(4));

        $this->expectException(WebAuthnVerificationException::class);
        $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['signCount' => 4]));
    }

    public function testBothZeroSignCountAllowed(): void
    {
        $credential = $this->activeCredential(0);
        $this->credentialRepository->method('getByCredentialId')->willReturn($credential);
        $this->credentialRepository->expects($this->once())->method('save');

        $result = $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['signCount' => 0]));

        $this->assertTrue($result->isVerified());
    }

    public function testUserVerificationRequiredButAbsentRejected(): void
    {
        $this->buildService($this->makeConfig(UserVerification::REQUIRED));
        $this->credentialRepository->method('getByCredentialId')->willReturn($this->activeCredential(1));

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('User verification is required but was not performed.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['uv' => false]));
    }

    public function testUserHandleMismatchRejected(): void
    {
        $this->credentialRepository->method('getByCredentialId')->willReturn($this->activeCredential(1));

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey user handle does not match.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE, ['userHandle' => '999']));
    }

    public function testConsumedChallengeReplayRejected(): void
    {
        // Simulate the guard rejecting an already-consumed challenge (replay).
        $guard = $this->createMock(ChallengeGuard::class);
        $guard->method('loadPending')->willThrowException(
            new WebAuthnVerificationException(__('The passkey challenge has already been used.'))
        );
        $this->buildService($this->makeConfig(UserVerification::PREFERRED), $guard);

        $this->expectException(WebAuthnVerificationException::class);
        $this->expectExceptionMessage('The passkey challenge has already been used.');
        $this->service->verify($this->vectors->assertion(self::CHALLENGE));
    }

    /**
     * Build (or rebuild) the service under test.
     *
     * @param ConfigProvider $configProvider
     * @param ChallengeGuard|null $guard
     * @return void
     */
    private function buildService(ConfigProvider $configProvider, ?ChallengeGuard $guard = null): void
    {
        $this->configProvider = $configProvider;
        $this->credentialRepository = $this->createMock(CredentialRepositoryInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->challengeGuard = $guard ?? $this->makeChallengeGuard();

        $relyingParty = $this->createMock(RelyingPartyProvider::class);
        $relyingParty->method('getId')->willReturn('example.com');
        $relyingParty->method('getOrigin')->willReturn('https://example.com');

        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtDate')->willReturn('2026-07-02 12:00:00');

        $derEncoder = new DerEncoder();

        $this->service = new AssertionVerificationService(
            $this->challengeGuard,
            new ClientDataParser(new Json()),
            new AuthenticatorDataParser(),
            new SignatureVerifier(new CoseKeyConverter($derEncoder), $derEncoder),
            $relyingParty,
            $this->configProvider,
            $this->credentialRepository,
            new Base64UrlEncoder(),
            $this->auditLogger,
            $dateTime,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * Build a challenge guard mock that returns a valid pending challenge and
     * records consumption.
     *
     * @return ChallengeGuard&MockObject
     */
    private function makeChallengeGuard(): ChallengeGuard
    {
        $guard = $this->createMock(ChallengeGuard::class);

        $challenge = $this->createMock(ChallengeInterface::class);
        $challenge->method('getChallenge')->willReturn(self::CHALLENGE);
        $challenge->method('getAdminUserId')->willReturn($this->challengeAdminUserId);

        $guard->method('loadPending')->willReturn($challenge);
        $guard->method('consume')->willReturnCallback(
            function () {
                $this->challengeConsumed = true;
            }
        );

        return $guard;
    }

    /**
     * Build a mocked ConfigProvider with a fixed user-verification policy.
     *
     * @param string $userVerification
     * @return ConfigProvider&MockObject
     */
    private function makeConfig(string $userVerification): ConfigProvider
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->method('getUserVerification')->willReturn($userVerification);

        return $configProvider;
    }

    /**
     * Build an active in-memory credential bound to the test vectors.
     *
     * @param int $signCount
     * @return InMemoryCredential
     */
    private function activeCredential(int $signCount): InMemoryCredential
    {
        $credential = new InMemoryCredential();
        $credential->setId(101)
            ->setAdminUserId(self::ADMIN_ID)
            ->setCredentialId($this->vectors->getCredentialId())
            ->setPublicKey($this->vectors->getEncodedCoseKey())
            ->setSignCount($signCount)
            ->setStatus(CredentialInterface::STATUS_ACTIVE);

        return $credential;
    }
}
