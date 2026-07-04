<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Passkey;

use Magento\Framework\Exception\LocalizedException;

/**
 * Normalises and validates the user-supplied friendly name for a passkey.
 *
 * Kept as a pure, dependency-free unit so the trimming/length/whitespace rules
 * can be unit-tested and reused by both the wizard "name your passkey" step and
 * the profile rename endpoint. Control characters are stripped and the value is
 * capped to the stored column length so it can never overflow the schema.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class FriendlyNameNormalizer
{
    /**
     * Maximum stored friendly-name length (matches the credential table column).
     */
    public const MAX_LENGTH = 255;

    /**
     * Normalise a raw friendly name, or throw when it is empty after cleaning.
     *
     * @param string $rawName
     * @return string
     * @throws LocalizedException
     */
    public function normalize(string $rawName): string
    {
        // Collapse whitespace (incl. tabs/newlines) to single spaces first, then drop
        // any remaining control characters so real spacing is preserved as spaces.
        $collapsed = (string) preg_replace('/\s+/u', ' ', $rawName);
        $stripped = (string) preg_replace('/[\p{C}]+/u', '', $collapsed);
        $name = trim($stripped);

        if ($name === '') {
            throw new LocalizedException(__('Please enter a name for this passkey.'));
        }

        return mb_substr($name, 0, self::MAX_LENGTH);
    }
}
