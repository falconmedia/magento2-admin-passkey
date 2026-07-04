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
use FalconMedia\AdminPasskey\Model\WebAuthn\AssertionVerificationServiceInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use FalconMedia\AdminPasskey\Model\WebAuthn\RelyingPartyProvider;
use FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn\WebAuthnTestVectors;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Repository-backed integration test for assertion verification.
 *
 * Uses a real admin user, a persisted credential and a real assertion challenge,
 * with a deterministic assertion vector generated for the resolved relying party.
 * No repository behaviour is mocked. Not run in CI (integration env unavailable);
 * see README for how to run integration tests.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class AssertionVerificationServiceTest extends TestCase
{
    public function testVerifyUpdatesCredentialAndConsumesChallenge(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Assert')
            ->setEmail('passkey.assert@example.com')
            ->setUsername('passkey_assert_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $adminUserId = (int) $user->getId();

        $relyingParty = $objectManager->get(RelyingPartyProvider::class);
        $vectors = new WebAuthnTestVectors($relyingParty->getId(), $relyingParty->getOrigin(), $adminUserId);

        /** @var CredentialRepositoryInterface $credentialRepository */
        $credentialRepository = $objectManager->get(CredentialRepositoryInterface::class);
        /** @var CredentialInterface $credential */
        $credential = $objectManager->create(CredentialInterface::class);
        $credential->setAdminUserId($adminUserId)
            ->setCredentialId($vectors->getCredentialId())
            ->setPublicKey($vectors->getEncodedCoseKey())
            ->setSignCount(0)
            ->setStatus(CredentialInterface::STATUS_ACTIVE);
        $credentialRepository->save($credential);

        /** @var ChallengeIssuer $challengeIssuer */
        $challengeIssuer = $objectManager->get(ChallengeIssuer::class);
        $challengeValue = $challengeIssuer->issue(ChallengeInterface::TYPE_ASSERTION, $adminUserId, '203.0.113.10');

        /** @var AssertionVerificationServiceInterface $service */
        $service = $objectManager->get(AssertionVerificationServiceInterface::class);
        $result = $service->verify($vectors->assertion($challengeValue, ['signCount' => 3]), '203.0.113.10');

        $this->assertTrue($result->isVerified());
        $this->assertSame($adminUserId, $result->getAdminUserId());
        $this->assertSame($vectors->getCredentialId(), $result->getCredentialId());

        $updated = $credentialRepository->getByCredentialId($vectors->getCredentialId());
        $this->assertSame(3, $updated->getSignCount());
        $this->assertNotEmpty($updated->getLastUsedAt());

        $this->assertChallengeConsumed($objectManager, $challengeValue);
    }

    public function testConsumedChallengeReplayRejected(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Replay')
            ->setEmail('passkey.replay@example.com')
            ->setUsername('passkey_replay_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $adminUserId = (int) $user->getId();

        $relyingParty = $objectManager->get(RelyingPartyProvider::class);
        $vectors = new WebAuthnTestVectors($relyingParty->getId(), $relyingParty->getOrigin(), $adminUserId);

        /** @var CredentialRepositoryInterface $credentialRepository */
        $credentialRepository = $objectManager->get(CredentialRepositoryInterface::class);
        /** @var CredentialInterface $credential */
        $credential = $objectManager->create(CredentialInterface::class);
        $credential->setAdminUserId($adminUserId)
            ->setCredentialId($vectors->getCredentialId())
            ->setPublicKey($vectors->getEncodedCoseKey())
            ->setSignCount(0)
            ->setStatus(CredentialInterface::STATUS_ACTIVE);
        $credentialRepository->save($credential);

        /** @var ChallengeIssuer $challengeIssuer */
        $challengeIssuer = $objectManager->get(ChallengeIssuer::class);
        $challengeValue = $challengeIssuer->issue(ChallengeInterface::TYPE_ASSERTION, $adminUserId, null);

        /** @var AssertionVerificationServiceInterface $service */
        $service = $objectManager->get(AssertionVerificationServiceInterface::class);

        // First use succeeds and consumes the challenge.
        $service->verify($vectors->assertion($challengeValue, ['signCount' => 1]));

        // Replaying the same (now consumed) challenge must fail.
        $this->expectException(WebAuthnVerificationException::class);
        $service->verify($vectors->assertion($challengeValue, ['signCount' => 2]));
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
