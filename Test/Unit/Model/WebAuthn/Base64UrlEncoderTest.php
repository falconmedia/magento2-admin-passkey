<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Model\WebAuthn\Base64UrlEncoder;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the RFC 4648 §5 base64url helper.
 */
class Base64UrlEncoderTest extends TestCase
{
    private Base64UrlEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new Base64UrlEncoder();
    }

    public function testEncodeProducesUrlSafeUnpaddedOutput(): void
    {
        // Bytes chosen so that standard base64 would contain '+', '/', and '=' padding.
        $binary = "\xff\xfe\xfd\xfc\xfb\xfa";
        $encoded = $this->encoder->encode($binary);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $encoded);
        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    public function testRoundTripPreservesArbitraryBytes(): void
    {
        $binary = random_bytes(32);

        $this->assertSame($binary, $this->encoder->decode($this->encoder->encode($binary)));
    }

    public function testKnownVector(): void
    {
        // "Hello" -> base64 "SGVsbG8=" -> base64url unpadded "SGVsbG8".
        $this->assertSame('SGVsbG8', $this->encoder->encode('Hello'));
        $this->assertSame('Hello', $this->encoder->decode('SGVsbG8'));
    }
}
