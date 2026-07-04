<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\RegistrationChallengeServiceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: building registration options for a real admin user persists a
 * registration challenge row with expires_at set.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class RegistrationChallengeServiceTest extends TestCase
{
    private RegistrationChallengeServiceInterface $service;
    private ChallengeRepositoryInterface $challengeRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private int $adminUserId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->service = $objectManager->get(RegistrationChallengeServiceInterface::class);
        $this->challengeRepository = $objectManager->get(ChallengeRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Registrant')
            ->setEmail('passkey.registrant@example.com')
            ->setUsername('passkey_registration_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $this->adminUserId = (int) $user->getId();
    }

    public function testCreateOptionsBuildsPayloadAndPersistsRegistrationChallenge(): void
    {
        $options = $this->service->createOptions($this->adminUserId, '203.0.113.10');

        $this->assertArrayHasKey('rp', $options);
        $this->assertArrayHasKey('user', $options);
        $this->assertArrayHasKey('challenge', $options);
        $this->assertArrayHasKey('pubKeyCredParams', $options);
        $this->assertArrayHasKey('authenticatorSelection', $options);
        $this->assertArrayHasKey('timeout', $options);
        $this->assertSame('passkey_registration_admin', $options['user']['name']);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $options['challenge']);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ChallengeInterface::ADMIN_USER_ID, $this->adminUserId)
            ->addFilter(ChallengeInterface::CHALLENGE_TYPE, ChallengeInterface::TYPE_REGISTRATION)
            ->create();
        $list = $this->challengeRepository->getList($searchCriteria);

        $this->assertGreaterThanOrEqual(1, $list->getTotalCount());

        $found = null;
        foreach ($list->getItems() as $challenge) {
            if ($challenge->getChallenge() === $options['challenge']) {
                $found = $challenge;
                break;
            }
        }

        $this->assertNotNull($found, 'The generated challenge was not persisted.');
        $this->assertSame(ChallengeInterface::TYPE_REGISTRATION, $found->getChallengeType());
        $this->assertSame(ChallengeInterface::STATUS_PENDING, $found->getStatus());
        $this->assertSame($this->adminUserId, $found->getAdminUserId());
        $this->assertNotEmpty($found->getExpiresAt());
    }
}
