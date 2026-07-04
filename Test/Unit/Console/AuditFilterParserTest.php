<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Console;

use FalconMedia\AdminPasskey\Console\AuditFilterParser;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the audit-export filter parser.
 */
class AuditFilterParserTest extends TestCase
{
    private AuditFilterParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AuditFilterParser();
    }

    public function testEmptyOptionsProduceEmptyFilter(): void
    {
        $filter = $this->parser->parse(null, null, null);

        $this->assertTrue($filter->isEmpty());
        $this->assertNull($filter->getFrom());
        $this->assertNull($filter->getTo());
        $this->assertNull($filter->getType());
    }

    public function testDateOnlyBoundsAreWidenedToWholeDay(): void
    {
        $filter = $this->parser->parse('2026-01-01', '2026-01-31', null);

        $this->assertSame('2026-01-01 00:00:00', $filter->getFrom());
        $this->assertSame('2026-01-31 23:59:59', $filter->getTo());
    }

    public function testFullDateTimeIsPreserved(): void
    {
        $filter = $this->parser->parse('2026-01-01 08:30:00', '2026-01-01 09:45:00', null);

        $this->assertSame('2026-01-01 08:30:00', $filter->getFrom());
        $this->assertSame('2026-01-01 09:45:00', $filter->getTo());
    }

    public function testTypeIsTrimmedAndBlankBecomesNull(): void
    {
        $this->assertSame('passkey_login', $this->parser->parse(null, null, '  passkey_login ')->getType());
        $this->assertNull($this->parser->parse(null, null, '   ')->getType());
    }

    public function testInvalidDateThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid --from date "2026-13-40"');

        $this->parser->parse('2026-13-40', null, null);
    }

    public function testInvertedRangeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The --from date must not be after the --to date.');

        $this->parser->parse('2026-02-01', '2026-01-01', null);
    }

    public function testEqualDateOnlyRangeIsValid(): void
    {
        $filter = $this->parser->parse('2026-01-01', '2026-01-01', null);

        $this->assertSame('2026-01-01 00:00:00', $filter->getFrom());
        $this->assertSame('2026-01-01 23:59:59', $filter->getTo());
    }
}
