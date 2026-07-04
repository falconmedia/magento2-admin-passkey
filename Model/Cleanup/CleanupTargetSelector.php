<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Cleanup;

/**
 * Pure selector that turns retention windows into per-category cutoff timestamps.
 *
 * A retention of zero (or less) disables cleanup for that category (returns null).
 * No I/O; the clock is passed in, so the selection is fully unit testable.
 */
class CleanupTargetSelector
{
    public const CATEGORY_CHALLENGES = 'challenges';
    public const CATEGORY_DIAGNOSTICS = 'diagnostics_reports';
    public const CATEGORY_AUDIT = 'audit_events';
    public const CATEGORY_SCORE_SNAPSHOTS = 'score_snapshots';
    public const CATEGORY_REMINDERS = 'reminders';

    /**
     * Resolve the cutoff timestamp for each category.
     *
     * @param string $now Current UTC time (Y-m-d H:i:s).
     * @param array<string, int> $retentionDays Category => retention window in days.
     * @return array<string, string|null> Category => cutoff (Y-m-d H:i:s) or null when disabled.
     */
    public function selectCutoffs(string $now, array $retentionDays): array
    {
        $base = $this->createDate($now);
        $cutoffs = [];
        foreach ($retentionDays as $category => $days) {
            $cutoffs[$category] = $this->resolveCutoff($base, (int) $days);
        }

        return $cutoffs;
    }

    /**
     * Resolve a single cutoff, or null when retention is non-positive.
     *
     * @param \DateTimeImmutable|null $base
     * @param int $days
     * @return string|null
     */
    private function resolveCutoff(?\DateTimeImmutable $base, int $days): ?string
    {
        if ($base === null || $days <= 0) {
            return null;
        }

        return $base->modify(sprintf('-%d days', $days))->format('Y-m-d H:i:s');
    }

    /**
     * Parse the supplied time, failing safe to null on invalid input.
     *
     * @param string $now
     * @return \DateTimeImmutable|null
     */
    private function createDate(string $now): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($now, new \DateTimeZone('UTC'));
        } catch (\Throwable) {
            return null;
        }
    }
}
