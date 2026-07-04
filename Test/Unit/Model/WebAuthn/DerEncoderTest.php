<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Model\WebAuthn\Asn1\DerEncoder;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the minimal ASN.1 DER encoder.
 */
class DerEncoderTest extends TestCase
{
    private DerEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new DerEncoder();
    }

    public function testShortLength(): void
    {
        $this->assertSame("\x00", $this->encoder->length(0));
        $this->assertSame("\x7f", $this->encoder->length(127));
    }

    public function testLongLength(): void
    {
        $this->assertSame("\x81\x80", $this->encoder->length(128));
        $this->assertSame("\x82\x01\x00", $this->encoder->length(256));
    }

    public function testIntegerStripsLeadingZeros(): void
    {
        // 0x00 0x01 -> INTEGER length 1 value 0x01.
        $this->assertSame("\x02\x01\x01", $this->encoder->integer("\x00\x01"));
    }

    public function testIntegerPrependsZeroWhenHighBitSet(): void
    {
        // 0x80 has the high bit set, so a leading 0x00 keeps it positive.
        $this->assertSame("\x02\x02\x00\x80", $this->encoder->integer("\x80"));
    }

    public function testIntegerZero(): void
    {
        $this->assertSame("\x02\x01\x00", $this->encoder->integer("\x00\x00"));
    }

    public function testSequenceWrapsContent(): void
    {
        $this->assertSame("\x30\x03\x02\x01\x05", $this->encoder->sequence("\x02\x01\x05"));
    }

    public function testBitStringPrependsUnusedBitsByte(): void
    {
        $this->assertSame("\x03\x03\x00\xaa\xbb", $this->encoder->bitString("\xaa\xbb"));
    }
}
