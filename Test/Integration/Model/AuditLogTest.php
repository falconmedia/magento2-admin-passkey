<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model;

use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for AuditLog repository.
 * Uses a real admin user and the repository contract only; no raw SQL and no mocks.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class AuditLogTest extends TestCase
{
    private AuditLogInterface $auditLog;
    private int $adminUserId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->auditLog = $objectManager->get(AuditLogInterface::class);

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Audit')
            ->setEmail('passkey.audit@example.com')
            ->setUsername('passkey_audit_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $this->adminUserId = (int) $user->getId();
    }

    public function testSaveGetByIdAndFilterByEventType(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $eventType = 'passkey_registration';

        /** @var AuditEventInterface $auditEvent */
        $auditEvent = $objectManager->create(AuditEventInterface::class);
        $auditEvent->setEventType($eventType)
            ->setActorAdminUserId($this->adminUserId)
            ->setTargetAdminUserId($this->adminUserId)
            ->setSeverity(AuditEventInterface::SEVERITY_INFO)
            ->setIp('203.0.113.20')
            ->setUserAgent('Mozilla/5.0 Test')
            ->setMetadata('{"source":"integration-test"}')
            ->setSupportReferenceId('SR-TEST-001');

        $saved = $this->auditLog->save($auditEvent);
        $entityId = $saved->getId();
        $this->assertNotNull($entityId);
        $this->assertGreaterThan(0, $entityId);

        $loaded = $this->auditLog->getById($entityId);
        $this->assertSame($eventType, $loaded->getEventType());
        $this->assertSame($this->adminUserId, $loaded->getActorAdminUserId());
        $this->assertSame(AuditEventInterface::SEVERITY_INFO, $loaded->getSeverity());
        $this->assertNotEmpty($loaded->getCreatedAt());

        $searchCriteria = $objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter(AuditEventInterface::EVENT_TYPE, $eventType)
            ->addFilter(AuditEventInterface::ACTOR_ADMIN_USER_ID, $this->adminUserId)
            ->create();
        $list = $this->auditLog->getList($searchCriteria);
        $this->assertGreaterThanOrEqual(1, $list->getTotalCount());

        $found = false;
        foreach ($list->getItems() as $item) {
            if ($item->getId() === $entityId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Saved audit event should be returned by getList filter');
    }
}
