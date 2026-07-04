<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Fail2Ban;

use FalconMedia\AdminPasskey\Model\Fail2Ban\Fail2BanLineFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the Fail2Ban log-line formatter.
 *
 * Asserts the single-line, parseable grammar, the placeholders for unknown fields
 * and — critically — that hostile input (newlines, quotes, spaces) can never break
 * the line or inject a second entry that Fail2Ban would misparse.
 */
class Fail2BanLineFormatterTest extends TestCase
{
    private Fail2BanLineFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new Fail2BanLineFormatter();
    }

    public function testFormatsFullyPopulatedLine(): void
    {
        $line = $this->formatter->format(
            '2026-01-01 10:00:00',
            'lockout',
            '203.0.113.7',
            'admin',
            42,
            'password'
        );

        $this->assertSame(
            '2026-01-01 10:00:00 FalconMedia_AdminPasskey event=lockout '
            . 'ip=203.0.113.7 user="admin" admin_id=42 method=password',
            $line
        );
    }

    public function testUsesPlaceholdersForUnknownFields(): void
    {
        $line = $this->formatter->format(
            '2026-01-01 10:00:00',
            'login_failed',
            null,
            null,
            null,
            null
        );

        $this->assertSame(
            '2026-01-01 10:00:00 FalconMedia_AdminPasskey event=login_failed '
            . 'ip=- user="-" admin_id=- method=-',
            $line
        );
    }

    public function testZeroAdminIdIsTreatedAsUnknown(): void
    {
        $line = $this->formatter->format('2026-01-01 10:00:00', 'login_failed', '10.0.0.1', 'bob', 0, 'passkey');

        $this->assertStringContainsString('admin_id=-', $line);
    }

    public function testLineIsAlwaysSingleLine(): void
    {
        $line = $this->formatter->format(
            "2026-01-01 10:00:00",
            "login_failed",
            "10.0.0.1\n192.168.0.1",
            "evil\nuser\r\nadmin_id=1",
            7,
            "passkey"
        );

        $this->assertStringNotContainsString("\n", $line);
        $this->assertStringNotContainsString("\r", $line);
    }

    public function testQuotedUserCannotEscapeItsField(): void
    {
        $line = $this->formatter->format(
            '2026-01-01 10:00:00',
            'login_failed',
            '10.0.0.1',
            'a" method=injected "b',
            null,
            'passkey'
        );

        // Inner double quotes are stripped, so only the two field delimiters remain:
        // the username value can never close its own quoted field.
        $this->assertSame(2, substr_count($line, '"'));
        $this->assertStringEndsWith('method=passkey', $line);
    }
}
