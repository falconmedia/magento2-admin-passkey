<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Diagnostics;

use FalconMedia\AdminPasskey\Model\Diagnostics\ManifestBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the diagnostics manifest builder.
 */
class ManifestBuilderTest extends TestCase
{
    private ManifestBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ManifestBuilder();
    }

    public function testBuildProducesExpectedStructure(): void
    {
        $manifest = $this->builder->build(
            [
                'support_reference_id' => 'FMAP-20260703-9F3A2C',
                'generated_at' => '2026-07-03 12:00:00',
                'versions' => ['module' => 'FalconMedia_AdminPasskey', 'php' => '8.3.0'],
                'counts' => ['admins_total' => 4],
                'health' => [
                    ['id' => 'https', 'label' => 'HTTPS', 'status' => 'ok', 'message' => 'ok'],
                    ['id' => 'cron', 'label' => 'Cron', 'status' => 'warning', 'message' => 'stale'],
                ],
                'score' => ['score' => 82, 'label' => 'Excellent'],
                'files' => ['manifest.json', 'health.json'],
                'config' => ['general_enabled' => true],
            ]
        );

        $this->assertSame(ManifestBuilder::MANIFEST_VERSION, $manifest['manifest_version']);
        $this->assertSame('FMAP-20260703-9F3A2C', $manifest['support_reference_id']);
        $this->assertSame('warning', $manifest['health']['overall']);
        $this->assertCount(2, $manifest['health']['checks']);
        $this->assertSame(82, $manifest['security_score']['score']);
        $this->assertSame(['manifest.json', 'health.json'], $manifest['files']);
        $this->assertArrayHasKey('notice', $manifest);
    }

    public function testOverallHealthIsErrorWhenAnyCheckErrors(): void
    {
        $manifest = $this->builder->build(
            [
                'health' => [
                    ['id' => 'a', 'label' => 'A', 'status' => 'ok', 'message' => ''],
                    ['id' => 'b', 'label' => 'B', 'status' => 'error', 'message' => ''],
                ],
            ]
        );

        $this->assertSame('error', $manifest['health']['overall']);
    }

    public function testOverallHealthIsOkWhenAllPass(): void
    {
        $manifest = $this->builder->build(
            [
                'health' => [
                    ['id' => 'a', 'label' => 'A', 'status' => 'ok', 'message' => ''],
                ],
            ]
        );

        $this->assertSame('ok', $manifest['health']['overall']);
    }
}
