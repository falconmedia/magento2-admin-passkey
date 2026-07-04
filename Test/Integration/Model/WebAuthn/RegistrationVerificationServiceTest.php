<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\ChallengeIssuer;
use FalconMedia\AdminPasskey\Model\WebAuthn\RegistrationVerificationServiceInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\RelyingPartyProvider;
use FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn\WebAuthnTestVectors;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Repository-backed integration test for registration verification.
 *
 * Uses a real admin user, the real challenge/credential repositories and a
 * deterministic attestation vector generated for the resolved relying party. No
 * repository behaviour is mocked. Not run in CI (integration env unavailable);
 * see README for how to run integration tests.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class RegistrationVerificationServiceTest extends TestCase
{
    public function testVerifyConsumesChallengeAndPersistsCredential(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Register')
            ->setEmail('passkey.register@example.com')
            ->setUsername('passkey_register_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $adminUserId = (int) $user->getId();

        $relyingParty = $objectManager->get(RelyingPartyProvider::class);
        $vectors = new WebAuthnTestVectors($relyingParty->getId(), $relyingParty->getOrigin(), $adminUserId);

        /** @var ChallengeIssuer $challengeIssuer */
        $challengeIssuer = $objectManager->get(ChallengeIssuer::class);
        $challengeValue = $challengeIssuer->issue(ChallengeInterface::TYPE_REGISTRATION, $adminUserId, '203.0.113.10');

        /** @var RegistrationVerificationServiceInterface $service */
        $service = $objectManager->get(RegistrationVerificationServiceInterface::class);
        $credential = $service->verify(
            $adminUserId,
            $vectors->registration($challengeValue, ['signCount' => 7]),
            '203.0.113.10'
        );

        $this->assertNotNull($credential->getId());
        $this->assertSame(CredentialInterface::STATUS_ACTIVE, $credential->getStatus());
        $this->assertSame(7, $credential->getSignCount());

        /** @var CredentialRepositoryInterface $credentialRepository */
        $credentialRepository = $objectManager->get(CredentialRepositoryInterface::class);
        $persisted = $credentialRepository->getByCredentialId($vectors->getCredentialId());
        $this->assertSame($adminUserId, $persisted->getAdminUserId());
        $this->assertSame($vectors->getEncodedCoseKey(), $persisted->getPublicKey());

        $this->assertChallengeConsumed($objectManager, $challengeValue);
    }

    public function testDuplicateCredentialIdRejected(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Dup')
            ->setEmail('passkey.dup@example.com')
            ->setUsername('passkey_dup_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $adminUserId = (int) $user->getId();

        $relyingParty = $objectManager->get(RelyingPartyProvider::class);
        $vectors = new WebAuthnTestVectors($relyingParty->getId(), $relyingParty->getOrigin(), $adminUserId);

        /** @var ChallengeIssuer $challengeIssuer */
        $challengeIssuer = $objectManager->get(ChallengeIssuer::class);
        /** @var RegistrationVerificationServiceInterface $service */
        $service = $objectManager->get(RegistrationVerificationServiceInterface::class);

        $firstChallenge = $challengeIssuer->issue(ChallengeInterface::TYPE_REGISTRATION, $adminUserId, null);
        $service->verify($adminUserId, $vectors->registration($firstChallenge));

        $secondChallenge = $challengeIssuer->issue(ChallengeInterface::TYPE_REGISTRATION, $adminUserId, null);

        $this->expectExceptionMessage('This passkey is already registered.');
        $service->verify($adminUserId, $vectors->registration($secondChallenge));
    }

    /**
     * Assert the challenge with the given value has been consumed.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $challengeValue
     * @return void
     */
    private function assertChallengeConsumed($objectManager, string $challengeValue): void
    {
        /** @var ChallengeRepositoryInterface $challengeRepository */
        $challengeRepository = $objectManager->get(ChallengeRepositoryInterface::class);
        $searchCriteria = $objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter(ChallengeInterface::CHALLENGE, $challengeValue)
            ->create();
        $items = $challengeRepository->getList($searchCriteria)->getItems();
        $challenge = $items[array_key_first($items)] ?? null;

        $this->assertNotNull($challenge);
        $this->assertSame(ChallengeInterface::STATUS_CONSUMED, $challenge->getStatus());
    }
}
