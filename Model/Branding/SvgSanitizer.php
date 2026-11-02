<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Branding;

use enshrined\svgSanitize\Sanitizer;
use Magento\Framework\Exception\LocalizedException;

/**
 * Allowlist-based SVG sanitizer for uploaded branding assets.
 *
 * Uploaded SVGs are served from the media URL on the Admin origin, so any script
 * in them is stored XSS that escalates "config write" rights to full Admin. A
 * blocklist is not enough (e.g. `<svg/onload=…>`, `&#106;avascript:`, UTF-16),
 * so the markup is rebuilt by the open-source enshrined/svg-sanitize allowlist
 * sanitizer (only known SVG elements/attributes survive, remote references are
 * removed). On top of that, the sanitized output must pass a strict check on
 * entity-decoded, whitespace-normalised text; anything still suspicious is
 * rejected rather than stored.
 *
 * Only UTF-8 (or ASCII) input is accepted.
 */
class SvgSanitizer
{
    /**
     * Markers that must not appear in the (decoded, whitespace-stripped) sanitized SVG.
     *
     * @var string[]
     */
    private const FORBIDDEN_MARKERS = [
        '<script',
        '<foreignobject',
        '<iframe',
        '<embed',
        '<object',
        '<style',
        '<!doctype',
        '<!entity',
        '<![cdata[',
        '<?xml-stylesheet',
        'javascript:',
        'vbscript:',
        'data:',
        '@import',
        'expression(',
        'url(http',
        'url(//',
    ];

    /**
     * Inline event handler attributes (onload=, onclick=, …), also after `/` or quotes.
     */
    private const EVENT_HANDLER_PATTERN = '#(?:^|[\s/"\'<])on[a-z]+\s*=#i';

    /**
     * Any href pointing outside the document (fragment references "#id" are fine).
     */
    private const EXTERNAL_HREF_PATTERN = '#href\s*=\s*["\']\s*(?!\#)[^"\']+#i';

    /**
     * Sanitize SVG markup and return the cleaned markup.
     *
     * @param string $svg
     * @return string
     * @throws LocalizedException When the SVG is not valid UTF-8, cannot be parsed, or is still unsafe.
     */
    public function sanitize(string $svg): string
    {
        $svg = $this->normaliseEncoding($svg);

        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);
        $clean = $sanitizer->sanitize($svg);

        if (!is_string($clean) || trim($clean) === '' || !$this->passesStrictCheck($clean)) {
            throw $this->rejected();
        }

        return $clean;
    }

    /**
     * Whether the given SVG markup can be stored (sanitizes cleanly and passes the strict check).
     *
     * @param string $svg
     * @return bool
     */
    public function isSafe(string $svg): bool
    {
        try {
            $this->sanitize($svg);
        } catch (LocalizedException) {
            return false;
        }

        // The original must not rely on the sanitizer silently dropping active content.
        return $this->passesStrictCheck($svg);
    }

    /**
     * Assert that the given SVG markup is safe, throwing when it is not.
     *
     * @param string $svg
     * @return void
     * @throws LocalizedException
     */
    public function assertSafe(string $svg): void
    {
        if (!$this->isSafe($svg)) {
            throw $this->rejected();
        }
    }

    /**
     * Strict check on decoded markup: no active content, no external references.
     *
     * @param string $svg
     * @return bool
     */
    private function passesStrictCheck(string $svg): bool
    {
        if (!$this->isUtf8($svg)) {
            return false;
        }

        // Decode entities repeatedly (&amp;#106; → &#106; → j) so encoded payloads are seen.
        $decoded = $svg;
        for ($i = 0; $i < 3; $i++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        if (preg_match(self::EVENT_HANDLER_PATTERN, $decoded) === 1
            || preg_match(self::EXTERNAL_HREF_PATTERN, $decoded) === 1
        ) {
            return false;
        }

        // Remove whitespace and control characters so "java\tscript:" cannot slip through.
        $compact = strtolower((string) preg_replace('/[\x00-\x20\x7f]+/u', '', $decoded));
        foreach (self::FORBIDDEN_MARKERS as $marker) {
            if (str_contains($compact, $marker)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Accept UTF-8 only (with or without BOM); reject UTF-16/32 and other encodings.
     *
     * @param string $svg
     * @return string UTF-8 markup without BOM.
     * @throws LocalizedException
     */
    private function normaliseEncoding(string $svg): string
    {
        if (str_starts_with($svg, "\xEF\xBB\xBF")) {
            $svg = substr($svg, 3);
        }

        if (!$this->isUtf8($svg) || str_contains($svg, "\x00")) {
            throw $this->rejected();
        }

        // An explicit non-UTF-8 XML encoding declaration is refused as well.
        if (preg_match('/<\?xml[^>]*encoding\s*=\s*["\']\s*(?!utf-?8\b)[^"\']*["\']/i', $svg) === 1) {
            throw $this->rejected();
        }

        return $svg;
    }

    /**
     * Whether the bytes are valid UTF-8.
     *
     * @param string $value
     * @return bool
     */
    private function isUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }

    /**
     * Generic rejection exception.
     *
     * @return LocalizedException
     */
    private function rejected(): LocalizedException
    {
        return new LocalizedException(
            __('The uploaded SVG contains scripting or external content and was rejected for security reasons.')
        );
    }
}
