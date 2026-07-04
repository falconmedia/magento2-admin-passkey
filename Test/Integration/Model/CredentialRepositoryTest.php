<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for CredentialRepository.
 * Uses a real admin user and the repository contract only; no raw SQL and no mocks.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class CredentialRepositoryTest extends TestCase
{
    private CredentialRepositoryInterface $repository;
    private int $adminUserId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(CredentialRepositoryInterface::class);

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Credential')
            ->setEmail('passkey.credential@example.com')
            ->setUsername('passkey_credential_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $this->adminUserId = (int) $user->getId();
    }

    public function testSaveGetRevokeAndList(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var CredentialInterface $credential */
        $credential = $objectManager->create(CredentialInterface::class);
        $credential->setAdminUserId($this->adminUserId)
            ->setCredentialId('cred-' . $this->adminUserId . '-abc123')
            ->setPublicKey('cose-public-key-material')
            ->setSignCount(0)
            ->setTransports('internal,hybrid')
            ->setFriendlyName('Test MacBook')
            ->setDeviceMetadata('{"platform":"macOS"}')
            ->setStatus(CredentialInterface::STATUS_ACTIVE);

        $saved = $this->repository->save($credential);
        $entityId = $saved->getId();
        $this->assertNotNull($entityId);
        $this->assertGreaterThan(0, $entityId);

        $loaded = $this->repository->getById($entityId);
        $this->assertSame($this->adminUserId, $loaded->getAdminUserId());
        $this->assertSame('cred-' . $this->adminUserId . '-abc123', $loaded->getCredentialId());
        $this->assertSame(CredentialInterface::STATUS_ACTIVE, $loaded->getStatus());
        $this->assertNotEmpty($loaded->getCreatedAt());

        $byCredentialId = $this->repository->getByCredentialId('cred-' . $this->adminUserId . '-abc123');
        $this->assertSame($entityId, $byCredentialId->getId());

        $activeList = $this->repository->listActiveForAdmin($this->adminUserId);
        $this->assertGreaterThanOrEqual(1, $activeList->getTotalCount());

        $revoked = $this->repository->revoke($entityId);
        $this->assertSame(CredentialInterface::STATUS_REVOKED, $revoked->getStatus());
        $this->assertNotEmpty($revoked->getRevokedAt());

        $afterRevoke = $this->repository->listActiveForAdmin($this->adminUserId);
        foreach ($afterRevoke->getItems() as $item) {
            $this->assertNotSame($entityId, $item->getId(), 'Revoked credential must not be active');
        }

        $searchCriteria = $objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter(CredentialInterface::ADMIN_USER_ID, $this->adminUserId)
            ->create();
        $list = $this->repository->getList($searchCriteria);
        $this->assertGreaterThanOrEqual(1, $list->getTotalCount());
    }
}
