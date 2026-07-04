<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Console;

use FalconMedia\AdminPasskey\Console\OutputFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Pure unit test for the shared CLI output formatter.
 */
class OutputFormatterTest extends TestCase
{
    private OutputFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new OutputFormatter();
    }

    public function testSupportedFormats(): void
    {
        $this->assertSame([OutputFormatter::FORMAT_TABLE, OutputFormatter::FORMAT_JSON], $this->formatter->getSupportedFormats());
    }

    public function testIsValidFormat(): void
    {
        $this->assertTrue($this->formatter->isValidFormat('table'));
        $this->assertTrue($this->formatter->isValidFormat('json'));
        $this->assertFalse($this->formatter->isValidFormat('xml'));
        $this->assertFalse($this->formatter->isValidFormat(''));
    }

    public function testIsJson(): void
    {
        $this->assertTrue($this->formatter->isJson('json'));
        $this->assertFalse($this->formatter->isJson('table'));
    }

    public function testAssertValidFormatThrowsOnUnknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format "yaml"');

        $this->formatter->assertValidFormat('yaml');
    }

    public function testAssertValidFormatAcceptsKnown(): void
    {
        $this->formatter->assertValidFormat('json');

        $this->addToAssertionCount(1);
    }

    public function testToJsonProducesPrettyValidJson(): void
    {
        $json = $this->formatter->toJson(['score' => 80, 'label' => 'Good']);

        $this->assertStringContainsString("\n", $json);
        $this->assertSame(['score' => 80, 'label' => 'Good'], json_decode($json, true));
    }

    public function testToJsonDoesNotEscapeSlashes(): void
    {
        $json = $this->formatter->toJson(['path' => 'var/log/app.log']);

        $this->assertStringContainsString('var/log/app.log', $json);
    }

    public function testRenderTableWritesHeadersAndRows(): void
    {
        $output = new BufferedOutput();

        $this->formatter->renderTable($output, ['ID', 'Name'], [['1', 'alpha'], ['2', 'beta']]);

        $rendered = $output->fetch();
        $this->assertStringContainsString('ID', $rendered);
        $this->assertStringContainsString('Name', $rendered);
        $this->assertStringContainsString('alpha', $rendered);
        $this->assertStringContainsString('beta', $rendered);
    }
}
