<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Passkey;

use FalconMedia\AdminPasskey\Model\Passkey\FriendlyNameNormalizer;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the passkey friendly-name normalisation rules.
 */
class FriendlyNameNormalizerTest extends TestCase
{
    private FriendlyNameNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new FriendlyNameNormalizer();
    }

    public function testTrimsSurroundingWhitespace(): void
    {
        $this->assertSame('Work laptop', $this->normalizer->normalize('  Work laptop  '));
    }

    public function testCollapsesInternalWhitespace(): void
    {
        $this->assertSame('Work laptop key', $this->normalizer->normalize("Work   laptop\tkey"));
    }

    public function testStripsControlCharacters(): void
    {
        $this->assertSame('YubiKey', $this->normalizer->normalize("Yubi\x00Key\x07"));
    }

    public function testTruncatesToMaxLength(): void
    {
        $result = $this->normalizer->normalize(str_repeat('a', FriendlyNameNormalizer::MAX_LENGTH + 25));

        $this->assertSame(FriendlyNameNormalizer::MAX_LENGTH, mb_strlen($result));
    }

    public function testThrowsWhenEmpty(): void
    {
        $this->expectException(LocalizedException::class);

        $this->normalizer->normalize('   ');
    }

    public function testThrowsWhenOnlyControlCharacters(): void
    {
        $this->expectException(LocalizedException::class);

        $this->normalizer->normalize("\x00\x01\x02");
    }
}
