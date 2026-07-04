<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console;

/**
 * Parses and validates the raw `--from`/`--to`/`--type` CLI options into an
 * immutable {@see AuditExportFilter}.
 *
 * Date-only bounds are widened to cover the whole day (from = start of day,
 * to = end of day) so `--from=2026-01-01 --to=2026-01-01` matches a full day.
 *
 * @internal Console support; not part of a public web API contract.
 */
class AuditFilterParser
{
    private const TZ = 'UTC';

    /**
     * Build a normalised filter from raw option strings.
     *
     * @param string|null $from
     * @param string|null $to
     * @param string|null $type
     * @return AuditExportFilter
     * @throws \InvalidArgumentException On an invalid date or an inverted range.
     */
    public function parse(?string $from, ?string $to, ?string $type): AuditExportFilter
    {
        $normalisedFrom = $this->normaliseDate($from, 'from', false);
        $normalisedTo = $this->normaliseDate($to, 'to', true);
        $normalisedType = $this->normaliseType($type);

        if ($normalisedFrom !== null && $normalisedTo !== null && $normalisedFrom > $normalisedTo) {
            throw new \InvalidArgumentException('The --from date must not be after the --to date.');
        }

        return new AuditExportFilter($normalisedFrom, $normalisedTo, $normalisedType);
    }

    /**
     * Normalise a date bound to a full UTC "Y-m-d H:i:s" string, or null.
     *
     * @param string|null $value
     * @param string $label
     * @param bool $endOfDay
     * @return string|null
     * @throws \InvalidArgumentException
     */
    private function normaliseDate(?string $value, string $label, bool $endOfDay): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $timezone = new \DateTimeZone(self::TZ);

        $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $trimmed, $timezone);
        if ($dateTime !== false && $dateTime->format('Y-m-d H:i:s') === $trimmed) {
            return $trimmed;
        }

        $dateOnly = \DateTimeImmutable::createFromFormat('!Y-m-d', $trimmed, $timezone);
        if ($dateOnly !== false && $dateOnly->format('Y-m-d') === $trimmed) {
            return $dateOnly->format('Y-m-d') . ($endOfDay ? ' 23:59:59' : ' 00:00:00');
        }

        throw new \InvalidArgumentException(
            sprintf('Invalid --%s date "%s". Use "Y-m-d" or "Y-m-d H:i:s" (UTC).', $label, $value)
        );
    }

    /**
     * Normalise an event-type filter to a trimmed, non-empty string, or null.
     *
     * @param string|null $type
     * @return string|null
     */
    private function normaliseType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $trimmed = trim($type);

        return $trimmed === '' ? null : $trimmed;
    }
}
