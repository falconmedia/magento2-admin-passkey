<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\TrustedDevice;

use FalconMedia\AdminPasskey\Model\TrustedDevice\TrustedDeviceTokenizer;
use Magento\Framework\Math\Random;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the trusted-device tokenizer.
 *
 * Asserts the storage contract: the raw token is never what gets persisted, only
 * its SHA-256 hash. Random is a framework boundary and is mocked.
 */
class TrustedDeviceTokenizerTest extends TestCase
{
    /**
     * @var Random&MockObject
     */
    private Random&MockObject $random;

    private TrustedDeviceTokenizer $tokenizer;

    protected function setUp(): void
    {
        $this->random = $this->createMock(Random::class);
        $this->tokenizer = new TrustedDeviceTokenizer($this->random);
    }

    public function testGenerateTokenRequestsSixtyFourRandomCharacters(): void
    {
        $this->random->expects($this->once())
            ->method('getRandomString')
            ->with(64)
            ->willReturn('raw-token-value');

        $this->assertSame('raw-token-value', $this->tokenizer->generateToken());
    }

    public function testHashIsSha256HexOfToken(): void
    {
        $token = 'raw-token-value';

        $this->assertSame(hash('sha256', $token), $this->tokenizer->hash($token));
    }

    public function testHashNeverEqualsRawToken(): void
    {
        $token = 'super-secret-device-token';

        $hash = $this->tokenizer->hash($token);

        $this->assertNotSame($token, $hash);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function testHashIsDeterministic(): void
    {
        $token = 'stable-token';

        $this->assertSame($this->tokenizer->hash($token), $this->tokenizer->hash($token));
    }
}
