<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Diagnostics;

use FalconMedia\AdminPasskey\Model\Diagnostics\LogSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the diagnostics log sanitiser.
 */
class LogSanitizerTest extends TestCase
{
    private LogSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new LogSanitizer();
    }

    public function testRedactsKeyValueSecrets(): void
    {
        $result = $this->sanitizer->sanitize('password=SuperSecret123 and token: abc123def');

        $this->assertStringNotContainsString('SuperSecret123', $result);
        $this->assertStringNotContainsString('abc123def', $result);
        $this->assertStringContainsString(LogSanitizer::REDACTED, $result);
    }

    public function testRedactsBearerTokens(): void
    {
        $result = $this->sanitizer->sanitize('Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9');

        $this->assertStringNotContainsString('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9', $result);
    }

    public function testRedactsLongOpaqueTokens(): void
    {
        $token = str_repeat('a1B2', 15);
        $result = $this->sanitizer->sanitize('challenge value is ' . $token);

        $this->assertStringNotContainsString($token, $result);
    }

    public function testKeepsRegularText(): void
    {
        $text = 'User admin logged in from 10.0.0.1 at 2026-07-03 12:00:00';
        $this->assertSame($text, $this->sanitizer->sanitize($text));
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', $this->sanitizer->sanitize(''));
    }

    public function testSanitizeArrayRedactsSensitiveKeys(): void
    {
        $result = $this->sanitizer->sanitizeArray(
            [
                'username' => 'admin',
                'password' => 'secret',
                'nested' => ['api_key' => 'xyz', 'note' => 'ok'],
            ]
        );

        $this->assertSame('admin', $result['username']);
        $this->assertSame(LogSanitizer::REDACTED, $result['password']);
        $this->assertSame(LogSanitizer::REDACTED, $result['nested']['api_key']);
        $this->assertSame('ok', $result['nested']['note']);
    }
}
