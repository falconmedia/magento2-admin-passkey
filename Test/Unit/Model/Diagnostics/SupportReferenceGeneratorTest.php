<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Diagnostics;

use FalconMedia\AdminPasskey\Model\Diagnostics\SupportReferenceGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the support reference builder/validator.
 */
class SupportReferenceGeneratorTest extends TestCase
{
    private SupportReferenceGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new SupportReferenceGenerator();
    }

    public function testGenerateBuildsUppercaseSanitizedReference(): void
    {
        $reference = $this->generator->generate('fmap', '2026-07-03', '9f3a2c');

        $this->assertSame('FMAP-20260703-9F3A2C', $reference);
        $this->assertTrue($this->generator->isValid($reference));
    }

    public function testGenerateFallsBackToDefaultPrefix(): void
    {
        $reference = $this->generator->generate('!!!', '20260703', 'abc');

        $this->assertStringStartsWith('FMAP-', $reference);
    }

    /**
     * @dataProvider validityProvider
     */
    public function testIsValid(string $reference, bool $expected): void
    {
        $this->assertSame($expected, $this->generator->isValid($reference));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function validityProvider(): array
    {
        return [
            'valid' => ['FMAP-20260703-9F3A2C', true],
            'lowercase' => ['fmap-20260703-9f3a2c', false],
            'missing-random' => ['FMAP-20260703-', false],
            'too-short-date' => ['FMAP-2026-AB', false],
            'empty' => ['', false],
        ];
    }
}
