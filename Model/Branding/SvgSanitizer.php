<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Branding;

use Magento\Framework\Exception\LocalizedException;

/**
 * Validates uploaded SVG markup and rejects constructs that enable script
 * execution, external entity expansion or embedded foreign content.
 *
 * This is intentionally conservative: any of the forbidden markers cause the
 * upload to be rejected outright rather than attempting to rewrite the file.
 * Pure logic; safe to unit-test with raw strings.
 */
class SvgSanitizer
{
    /**
     * Case-insensitive markers whose presence marks an SVG as unsafe.
     *
     * @var string[]
     */
    private const FORBIDDEN_MARKERS = [
        '<script',
        '<foreignobject',
        '<iframe',
        '<embed',
        '<object',
        '<!doctype',
        '<!entity',
        '<![cdata[',
        'javascript:',
        'data:text/html',
    ];

    /**
     * Regex matching inline event handler attributes such as onload= or onclick=.
     */
    private const EVENT_HANDLER_PATTERN = '/\son[a-z]+\s*=/i';

    /**
     * Whether the given SVG markup is considered safe to store.
     *
     * @param string $svg
     * @return bool
     */
    public function isSafe(string $svg): bool
    {
        $haystack = strtolower($svg);

        foreach (self::FORBIDDEN_MARKERS as $marker) {
            if (str_contains($haystack, $marker)) {
                return false;
            }
        }

        return preg_match(self::EVENT_HANDLER_PATTERN, $svg) !== 1;
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
            throw new LocalizedException(
                __('The uploaded SVG contains scripting or external content and was rejected for security reasons.')
            );
        }
    }
}
