<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Audit;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterfaceFactory;
use FalconMedia\AdminPasskey\Model\Audit\AuditLogger;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\HTTP\Header as HttpHeader;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the audit logging service.
 *
 * The repository, factory and framework request/session boundaries are mocked,
 * so the test asserts the event-code -> severity mapping and payload building
 * (including secret redaction) without touching the database.
 */
class AuditLoggerTest extends TestCase
{
    /**
     * @var AuditLogInterface&MockObject
     */
    private AuditLogInterface&MockObject $auditLog;

    /**
     * @var AuditEventInterfaceFactory&MockObject
     */
    private AuditEventInterfaceFactory&MockObject $auditEventFactory;

    /**
     * @var RemoteAddress&MockObject
     */
    private RemoteAddress&MockObject $remoteAddress;

    /**
     * @var HttpHeader&MockObject
     */
    private HttpHeader&MockObject $httpHeader;

    /**
     * @var AdminSession&MockObject
     */
    private AdminSession&MockObject $adminSession;

    /**
     * @var AuditLogger
     */
    private AuditLogger $auditLogger;

    /**
     * Captured data set on the audit event entity by the service under test.
     *
     * @var array<string, mixed>
     */
    private array $captured = [];

    protected function setUp(): void
    {
        $this->auditLog = $this->createMock(AuditLogInterface::class);
        $this->auditEventFactory = $this->getMockBuilder(AuditEventInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->remoteAddress = $this->createMock(RemoteAddress::class);
        $this->httpHeader = $this->createMock(HttpHeader::class);
        $this->adminSession = $this->getMockBuilder(AdminSession::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUser'])
            ->getMock();

        $json = $this->createMock(Json::class);
        $json->method('serialize')->willReturnCallback(
            static fn ($data): string => (string) json_encode($data)
        );

        $event = $this->createCapturingEvent();
        $this->auditEventFactory->method('create')->willReturn($event);
        $this->auditLog->method('save')->willReturnArgument(0);

        $this->auditLogger = new AuditLogger(
            $this->auditLog,
            $this->auditEventFactory,
            $json,
            $this->remoteAddress,
            $this->httpHeader,
            $this->adminSession
        );
    }

    /**
     * @dataProvider severityMappingProvider
     */
    public function testResolveSeverityMapping(string $eventType, string $expectedSeverity): void
    {
        $this->assertSame($expectedSeverity, $this->auditLogger->resolveSeverity($eventType));
    }

    /**
     * @dataProvider severityMappingProvider
     */
    public function testRecordAppliesMappedSeverity(string $eventType, string $expectedSeverity): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);
        $this->httpHeader->method('getHttpUserAgent')->willReturn('');
        $this->adminSession->method('getUser')->willReturn(null);

        $this->auditLogger->record($eventType);

        $this->assertSame($eventType, $this->captured['event_type']);
        $this->assertSame($expectedSeverity, $this->captured['severity']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function severityMappingProvider(): array
    {
        return [
            'registration' => [AuditLoggerInterface::EVENT_PASSKEY_REGISTRATION, AuditEventInterface::SEVERITY_INFO],
            'login' => [AuditLoggerInterface::EVENT_PASSKEY_LOGIN, AuditEventInterface::SEVERITY_INFO],
            'passkey revoke' => [AuditLoggerInterface::EVENT_PASSKEY_REVOKE, AuditEventInterface::SEVERITY_NOTICE],
            'device revoke' => [
                AuditLoggerInterface::EVENT_TRUSTED_DEVICE_REVOKE,
                AuditEventInterface::SEVERITY_NOTICE,
            ],
            'lockout' => [AuditLoggerInterface::EVENT_LOCKOUT, AuditEventInterface::SEVERITY_WARNING],
            'unlock' => [AuditLoggerInterface::EVENT_UNLOCK, AuditEventInterface::SEVERITY_NOTICE],
            'recovery enable' => [AuditLoggerInterface::EVENT_RECOVERY_ENABLE, AuditEventInterface::SEVERITY_CRITICAL],
            'recovery disable' => [AuditLoggerInterface::EVENT_RECOVERY_DISABLE, AuditEventInterface::SEVERITY_NOTICE],
            'diag generate' => [AuditLoggerInterface::EVENT_DIAGNOSTICS_GENERATE, AuditEventInterface::SEVERITY_INFO],
            'diag send' => [AuditLoggerInterface::EVENT_DIAGNOSTICS_SEND, AuditEventInterface::SEVERITY_NOTICE],
            'cleanup' => [AuditLoggerInterface::EVENT_CLEANUP, AuditEventInterface::SEVERITY_INFO],
            'reminder' => [AuditLoggerInterface::EVENT_MIGRATION_REMINDER, AuditEventInterface::SEVERITY_INFO],
            'config change' => [AuditLoggerInterface::EVENT_CONFIG_CHANGE, AuditEventInterface::SEVERITY_NOTICE],
            'unknown falls back to info' => ['not_a_real_code', AuditEventInterface::SEVERITY_INFO],
        ];
    }

    public function testRecordBuildsPayloadFromContext(): void
    {
        $this->auditLogger->record(
            AuditLoggerInterface::EVENT_LOCKOUT,
            [
                AuditLoggerInterface::CONTEXT_ACTOR => 5,
                AuditLoggerInterface::CONTEXT_TARGET => 9,
                AuditLoggerInterface::CONTEXT_IP => '203.0.113.7',
                AuditLoggerInterface::CONTEXT_USER_AGENT => 'Mozilla/5.0 Test',
                AuditLoggerInterface::CONTEXT_SUPPORT_REFERENCE_ID => 'SR-123',
                AuditLoggerInterface::CONTEXT_SEVERITY => AuditEventInterface::SEVERITY_CRITICAL,
                AuditLoggerInterface::CONTEXT_METADATA => ['reason' => 'too_many_attempts', 'attempts' => 6],
            ]
        );

        $this->assertSame(5, $this->captured['actor_admin_user_id']);
        $this->assertSame(9, $this->captured['target_admin_user_id']);
        $this->assertSame('203.0.113.7', $this->captured['ip']);
        $this->assertSame('Mozilla/5.0 Test', $this->captured['user_agent']);
        $this->assertSame('SR-123', $this->captured['support_reference_id']);
        $this->assertSame(AuditEventInterface::SEVERITY_CRITICAL, $this->captured['severity']);

        $metadata = json_decode((string) $this->captured['metadata'], true);
        $this->assertSame('too_many_attempts', $metadata['reason']);
        $this->assertSame(6, $metadata['attempts']);
    }

    public function testRecordAutoResolvesActorIpAndUserAgent(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $user->method('getId')->willReturn(7);

        $this->adminSession->method('getUser')->willReturn($user);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('198.51.100.5');
        $this->httpHeader->method('getHttpUserAgent')->willReturn('AutoAgent/2.0');

        $this->auditLogger->record(AuditLoggerInterface::EVENT_PASSKEY_LOGIN);

        $this->assertSame(7, $this->captured['actor_admin_user_id']);
        $this->assertSame('198.51.100.5', $this->captured['ip']);
        $this->assertSame('AutoAgent/2.0', $this->captured['user_agent']);
    }

    public function testRecordRedactsSensitiveMetadataKeys(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);
        $this->httpHeader->method('getHttpUserAgent')->willReturn('');
        $this->adminSession->method('getUser')->willReturn(null);

        $this->auditLogger->record(
            AuditLoggerInterface::EVENT_PASSKEY_REGISTRATION,
            [
                AuditLoggerInterface::CONTEXT_METADATA => [
                    'friendly_name' => 'YubiKey 5',
                    'password' => 'super-secret',
                    'nested' => ['api_token' => 'abc123', 'safe' => 'ok'],
                ],
            ]
        );

        $metadata = json_decode((string) $this->captured['metadata'], true);
        $this->assertSame('YubiKey 5', $metadata['friendly_name']);
        $this->assertSame('[redacted]', $metadata['password']);
        $this->assertSame('[redacted]', $metadata['nested']['api_token']);
        $this->assertSame('ok', $metadata['nested']['safe']);
    }

    public function testRecordStoresNullMetadataWhenEmpty(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);
        $this->httpHeader->method('getHttpUserAgent')->willReturn('');
        $this->adminSession->method('getUser')->willReturn(null);

        $this->auditLogger->record(AuditLoggerInterface::EVENT_CLEANUP);

        $this->assertNull($this->captured['metadata']);
    }

    public function testRecordPersistsViaRepository(): void
    {
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);
        $this->httpHeader->method('getHttpUserAgent')->willReturn('');
        $this->adminSession->method('getUser')->willReturn(null);

        $this->auditLog->expects($this->once())->method('save');

        $result = $this->auditLogger->record(AuditLoggerInterface::EVENT_CLEANUP);

        $this->assertInstanceOf(AuditEventInterface::class, $result);
    }

    /**
     * Build an audit event mock that records set values into $this->captured.
     *
     * @return AuditEventInterface&MockObject
     */
    private function createCapturingEvent(): AuditEventInterface&MockObject
    {
        $event = $this->createMock(AuditEventInterface::class);

        $setters = [
            'setEventType' => AuditEventInterface::EVENT_TYPE,
            'setSeverity' => AuditEventInterface::SEVERITY,
            'setActorAdminUserId' => AuditEventInterface::ACTOR_ADMIN_USER_ID,
            'setTargetAdminUserId' => AuditEventInterface::TARGET_ADMIN_USER_ID,
            'setIp' => AuditEventInterface::IP,
            'setUserAgent' => AuditEventInterface::USER_AGENT,
            'setSupportReferenceId' => AuditEventInterface::SUPPORT_REFERENCE_ID,
            'setMetadata' => AuditEventInterface::METADATA,
        ];

        foreach ($setters as $method => $key) {
            $event->method($method)->willReturnCallback(
                function ($value) use ($event, $key) {
                    $this->captured[$key] = $value;
                    return $event;
                }
            );
        }

        return $event;
    }
}
