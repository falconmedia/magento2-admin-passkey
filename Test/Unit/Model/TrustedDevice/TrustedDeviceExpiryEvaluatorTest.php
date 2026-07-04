<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\TrustedDevice;

use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use FalconMedia\AdminPasskey\Model\TrustedDevice\TrustedDeviceExpiryEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for trusted-device expiry and validity decisions.
 *
 * All time inputs are passed in, so the resolve-expiry, is-expired and is-valid
 * rules are asserted without any clock or database coupling.
 */
class TrustedDeviceExpiryEvaluatorTest extends TestCase
{
    private TrustedDeviceExpiryEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new TrustedDeviceExpiryEvaluator();
    }

    public function testResolveExpiresAtAddsLifetime(): void
    {
        $result = $this->evaluator->resolveExpiresAt('2026-01-01 00:00:00', 30);

        $this->assertSame('2026-01-31 00:00:00', $result);
    }

    /**
     * @dataProvider neverExpiresProvider
     */
    public function testResolveExpiresAtReturnsNullForNonPositiveLifetime(int $lifetimeDays): void
    {
        $this->assertNull($this->evaluator->resolveExpiresAt('2026-01-01 00:00:00', $lifetimeDays));
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function neverExpiresProvider(): array
    {
        return [
            'zero lifetime' => [0],
            'negative lifetime' => [-5],
        ];
    }

    /**
     * @dataProvider expiredProvider
     */
    public function testIsExpired(?string $expiresAt, string $now, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator->isExpired($expiresAt, $now));
    }

    /**
     * @return array<string, array{0: string|null, 1: string, 2: bool}>
     */
    public static function expiredProvider(): array
    {
        return [
            'null never expires' => [null, '2026-01-01 10:00:00', false],
            'empty never expires' => ['', '2026-01-01 10:00:00', false],
            'past is expired' => ['2026-01-01 09:00:00', '2026-01-01 10:00:00', true],
            'exact now is expired' => ['2026-01-01 10:00:00', '2026-01-01 10:00:00', true],
            'future is not expired' => ['2026-01-01 11:00:00', '2026-01-01 10:00:00', false],
        ];
    }

    /**
     * @dataProvider validProvider
     */
    public function testIsValid(?string $status, ?string $expiresAt, string $now, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator->isValid($status, $expiresAt, $now));
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null, 2: string, 3: bool}>
     */
    public static function validProvider(): array
    {
        return [
            'active and future' => [
                TrustedDeviceInterface::STATUS_ACTIVE,
                '2026-01-01 11:00:00',
                '2026-01-01 10:00:00',
                true,
            ],
            'active and never expires' => [
                TrustedDeviceInterface::STATUS_ACTIVE,
                null,
                '2026-01-01 10:00:00',
                true,
            ],
            'active but expired' => [
                TrustedDeviceInterface::STATUS_ACTIVE,
                '2026-01-01 09:00:00',
                '2026-01-01 10:00:00',
                false,
            ],
            'revoked is never valid' => [
                TrustedDeviceInterface::STATUS_REVOKED,
                '2026-01-01 11:00:00',
                '2026-01-01 10:00:00',
                false,
            ],
            'expired status is never valid' => [
                TrustedDeviceInterface::STATUS_EXPIRED,
                '2026-01-01 11:00:00',
                '2026-01-01 10:00:00',
                false,
            ],
        ];
    }
}
