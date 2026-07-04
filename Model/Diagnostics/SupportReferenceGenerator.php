<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Diagnostics;

/**
 * Pure builder/validator for Support Reference IDs.
 *
 * A reference has the shape PREFIX-YYYYMMDD-RANDOM (e.g. FMAP-20260703-9F3A2C).
 * No I/O; the caller supplies the prefix, date and random parts so it is fully
 * unit testable.
 */
class SupportReferenceGenerator
{
    private const DEFAULT_PREFIX = 'FMAP';

    /**
     * Build a Support Reference ID from its parts.
     *
     * @param string $prefix
     * @param string $datePart Digits only (e.g. 20260703).
     * @param string $randomPart Alphanumeric random component.
     * @return string
     */
    public function generate(string $prefix, string $datePart, string $randomPart): string
    {
        $safePrefix = $this->sanitizeAlnumUpper($prefix);
        if ($safePrefix === '') {
            $safePrefix = self::DEFAULT_PREFIX;
        }
        $safeDate = preg_replace('/[^0-9]/', '', $datePart) ?? '';
        $safeRandom = $this->sanitizeAlnumUpper($randomPart);

        return $safePrefix . '-' . $safeDate . '-' . $safeRandom;
    }

    /**
     * Validate the shape of a Support Reference ID.
     *
     * @param string $reference
     * @return bool
     */
    public function isValid(string $reference): bool
    {
        return (bool) preg_match('/^[A-Z0-9]+-[0-9]{6,}-[A-Z0-9]+$/', $reference);
    }

    /**
     * Upper-case and strip non-alphanumeric characters.
     *
     * @param string $value
     * @return string
     */
    private function sanitizeAlnumUpper(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');
    }
}
