<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model\Audit;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for the audit logging service.
 * Uses the service contracts only (no raw SQL, no mocks) against a real admin user.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class AuditLoggerTest extends TestCase
{
    private AuditLoggerInterface $auditLogger;
    private AuditLogInterface $auditLog;
    private CredentialRepositoryInterface $credentialRepository;
    private int $adminUserId;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->auditLogger = $objectManager->get(AuditLoggerInterface::class);
        $this->auditLog = $objectManager->get(AuditLogInterface::class);
        $this->credentialRepository = $objectManager->get(CredentialRepositoryInterface::class);

        /** @var User $user */
        $user = $objectManager->create(User::class);
        $user->setFirstname('Passkey')
            ->setLastname('Logger')
            ->setEmail('passkey.logger@example.com')
            ->setUsername('passkey_logger_admin')
            ->setPassword('Passkey123!Secret')
            ->setIsActive(1)
            ->save();
        $this->adminUserId = (int) $user->getId();
    }

    public function testRecordPersistsAuditEventWithMappedSeverity(): void
    {
        $recorded = $this->auditLogger->record(
            AuditLoggerInterface::EVENT_LOCKOUT,
            [
                AuditLoggerInterface::CONTEXT_TARGET => $this->adminUserId,
                AuditLoggerInterface::CONTEXT_SUPPORT_REFERENCE_ID => 'SR-INT-001',
                AuditLoggerInterface::CONTEXT_METADATA => ['reason' => 'too_many_attempts'],
            ]
        );

        $entityId = $recorded->getId();
        $this->assertNotNull($entityId);

        $loaded = $this->auditLog->getById($entityId);
        $this->assertSame(AuditLoggerInterface::EVENT_LOCKOUT, $loaded->getEventType());
        $this->assertSame(AuditEventInterface::SEVERITY_WARNING, $loaded->getSeverity());
        $this->assertSame($this->adminUserId, $loaded->getTargetAdminUserId());
        $this->assertSame('SR-INT-001', $loaded->getSupportReferenceId());
        $this->assertNotEmpty($loaded->getCreatedAt());
        $this->assertStringContainsString('too_many_attempts', (string) $loaded->getMetadata());
    }

    public function testRecordRedactsSecretsBeforePersisting(): void
    {
        $recorded = $this->auditLogger->record(
            AuditLoggerInterface::EVENT_PASSKEY_REGISTRATION,
            [
                AuditLoggerInterface::CONTEXT_METADATA => [
                    'friendly_name' => 'YubiKey 5',
                    'password' => 'super-secret',
                ],
            ]
        );

        $metadata = (string) $this->auditLog->getById($recorded->getId())->getMetadata();
        $this->assertStringContainsString('YubiKey 5', $metadata);
        $this->assertStringNotContainsString('super-secret', $metadata);
    }

    public function testCredentialRevokeWritesAuditRow(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var CredentialInterface $credential */
        $credential = $objectManager->create(CredentialInterface::class);
        $credential->setAdminUserId($this->adminUserId)
            ->setCredentialId('cred-' . $this->adminUserId . '-audit')
            ->setPublicKey('cose-public-key-material')
            ->setSignCount(0)
            ->setFriendlyName('Audit Test Key')
            ->setStatus(CredentialInterface::STATUS_ACTIVE);

        $saved = $this->credentialRepository->save($credential);
        $this->credentialRepository->revoke((int) $saved->getId());

        $searchCriteria = $objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter(AuditEventInterface::EVENT_TYPE, AuditLoggerInterface::EVENT_PASSKEY_REVOKE)
            ->addFilter(AuditEventInterface::TARGET_ADMIN_USER_ID, $this->adminUserId)
            ->create();

        $list = $this->auditLog->getList($searchCriteria);
        $this->assertGreaterThanOrEqual(1, $list->getTotalCount(), 'Credential revoke must write an audit row');

        $found = false;
        foreach ($list->getItems() as $item) {
            if ($item->getEventType() === AuditLoggerInterface::EVENT_PASSKEY_REVOKE) {
                $found = true;
                $this->assertSame(AuditEventInterface::SEVERITY_NOTICE, $item->getSeverity());
                break;
            }
        }
        $this->assertTrue($found, 'A passkey_revoke audit event should exist for the revoked credential');
    }
}
