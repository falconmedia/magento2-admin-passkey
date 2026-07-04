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
use FalconMedia\AdminPasskey\Model\WebAuthn\AssertionChallengeServiceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: building assertion options persists an assertion challenge row,
 * both for known credentials (real admin + credential) and discoverable credentials.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class AssertionChallengeServiceTest extends TestCase
{
    private AssertionChallengeServiceInterface $service;
    private ChallengeRepositoryInterface $challengeRepository;
    private CredentialRepositoryInterface $credentialRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private int $adminUserId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->service = $objectManager->get(AssertionChallengeServiceInterface::class);
        $this->challengeRepository = $objectManager->get(ChallengeRepositoryInterface::class);
        $this->credentialRepository = $objectManager->get(CredentialRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Asserter')
            ->setEmail('passkey.asserter@example.com')
            ->setUsername('passkey_assertion_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $this->adminUserId = (int) $user->getId();

        /** @var CredentialInterface $credential */
        $credential = $objectManager->create(CredentialInterface::class);
        $credential->setAdminUserId($this->adminUserId)
            ->setCredentialId('assertion-known-credential-id')
            ->setPublicKey('-----BEGIN PUBLIC KEY-----test-----END PUBLIC KEY-----')
            ->setSignCount(0)
            ->setTransports('["internal","hybrid"]')
            ->setFriendlyName('Integration Passkey')
            ->setStatus(CredentialInterface::STATUS_ACTIVE);
        $this->credentialRepository->save($credential);
    }

    public function testKnownCredentialsPersistAssertionChallenge(): void
    {
        $options = $this->service->createOptions($this->adminUserId, '203.0.113.10');

        $this->assertArrayHasKey('challenge', $options);
        $this->assertArrayHasKey('allowCredentials', $options);
        $this->assertContains($options['userVerification'], ['required', 'preferred', 'discouraged']);
        $this->assertNotEmpty($options['allowCredentials']);
        $this->assertSame('assertion-known-credential-id', $options['allowCredentials'][0]['id']);
        $this->assertSame(['internal', 'hybrid'], $options['allowCredentials'][0]['transports']);

        $found = $this->findChallenge($options['challenge']);
        $this->assertNotNull($found);
        $this->assertSame(ChallengeInterface::TYPE_ASSERTION, $found->getChallengeType());
        $this->assertSame($this->adminUserId, $found->getAdminUserId());
        $this->assertNotEmpty($found->getExpiresAt());
    }

    public function testDiscoverableCredentialsPersistAssertionChallengeWithoutAdminUser(): void
    {
        $options = $this->service->createOptions();

        $this->assertSame([], $options['allowCredentials']);

        $found = $this->findChallenge($options['challenge']);
        $this->assertNotNull($found);
        $this->assertSame(ChallengeInterface::TYPE_ASSERTION, $found->getChallengeType());
        $this->assertNull($found->getAdminUserId());
        $this->assertNotEmpty($found->getExpiresAt());
    }

    private function findChallenge(string $challengeValue): ?ChallengeInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ChallengeInterface::CHALLENGE, $challengeValue)
            ->create();

        foreach ($this->challengeRepository->getList($searchCriteria)->getItems() as $challenge) {
            return $challenge;
        }

        return null;
    }
}
