<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Branding;

use FalconMedia\AdminPasskey\Model\Branding\SvgSanitizer;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the SVG upload safety validator.
 */
class SvgSanitizerTest extends TestCase
{
    private SvgSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new SvgSanitizer();
    }

    public function testAcceptsCleanSvg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
            . '<path d="M4 4h16v16H4z" fill="#1f2937"/></svg>';

        $this->assertTrue($this->sanitizer->isSafe($svg));
        $this->sanitizer->assertSafe($svg);
    }

    /**
     * @dataProvider unsafeSvgProvider
     */
    public function testRejectsUnsafeSvg(string $svg): void
    {
        $this->assertFalse($this->sanitizer->isSafe($svg));
    }

    /**
     * @dataProvider unsafeSvgProvider
     */
    public function testAssertSafeThrowsOnUnsafeSvg(string $svg): void
    {
        $this->expectException(LocalizedException::class);
        $this->sanitizer->assertSafe($svg);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unsafeSvgProvider(): array
    {
        return [
            'script tag' => ['<svg><script>alert(1)</script></svg>'],
            'onload handler' => ['<svg onload="alert(1)"><path/></svg>'],
            'onclick handler' => ['<svg><rect onclick="steal()"/></svg>'],
            'foreignObject' => ['<svg><foreignObject><body>x</body></foreignObject></svg>'],
            'doctype' => ['<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"><svg></svg>'],
            'entity' => ['<!ENTITY xxe SYSTEM "file:///etc/passwd"><svg></svg>'],
            'javascript uri' => ['<svg><a xlink:href="javascript:alert(1)">x</a></svg>'],
            'iframe' => ['<svg><iframe src="https://evil.example"></iframe></svg>'],
        ];
    }
}
