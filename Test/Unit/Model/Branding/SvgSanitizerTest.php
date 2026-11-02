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
 * Unit test for the SVG upload sanitizer (requires enshrined/svg-sanitize).
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

    public function testAcceptsInternalReferencesAndUtf8Bom(): void
    {
        $svg = "\xEF\xBB\xBF" . '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'
            . '<defs><linearGradient id="g"/></defs><use xlink:href="#g"/><rect fill="url(#g)"/></svg>';

        $this->assertTrue($this->sanitizer->isSafe($svg));
        $this->assertStringContainsString('<svg', $this->sanitizer->sanitize($svg));
    }

    public function testSanitizeThrowsOnUnparsableMarkup(): void
    {
        $this->expectException(LocalizedException::class);
        $this->sanitizer->sanitize('not an svg');
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
            'slash onload (no whitespace)' => ['<svg/onload=alert(1)>'],
            'quote-adjacent onclick' => ['<svg><rect x="1"onclick="x()"/></svg>'],
            'entity encoded javascript' => ['<svg><a href="&#106;avascript:alert(1)">x</a></svg>'],
            'double entity encoded javascript' => ['<svg><a href="&amp;#106;avascript:alert(1)">x</a></svg>'],
            'tab inside javascript' => ['<svg><a href="java&#9;script:alert(1)">x</a></svg>'],
            'utf-16 encoded' => [mb_convert_encoding('<svg onload="alert(1)"></svg>', 'UTF-16')],
            'non utf-8 declaration' => ['<?xml version="1.0" encoding="ISO-8859-1"?><svg></svg>'],
            'remote use reference' => ['<svg><use href="https://evil.example/x.svg#a"/></svg>'],
            'style import' => ['<svg><style>@import url(https://evil.example/x.css);</style></svg>'],
        ];
    }
}
