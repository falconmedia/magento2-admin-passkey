<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Cleanup;

use FalconMedia\AdminPasskey\Model\Cleanup\CleanupTargetSelector;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the retention cutoff selection.
 */
class CleanupTargetSelectorTest extends TestCase
{
    private CleanupTargetSelector $selector;

    protected function setUp(): void
    {
        $this->selector = new CleanupTargetSelector();
    }

    public function testCutoffsAreComputedAndDisabledWhenNonPositive(): void
    {
        $cutoffs = $this->selector->selectCutoffs(
            '2026-07-03 12:00:00',
            [
                CleanupTargetSelector::CATEGORY_CHALLENGES => 7,
                CleanupTargetSelector::CATEGORY_AUDIT => 0,
                CleanupTargetSelector::CATEGORY_REMINDERS => 30,
                CleanupTargetSelector::CATEGORY_SCORE_SNAPSHOTS => -5,
            ]
        );

        $this->assertSame('2026-06-26 12:00:00', $cutoffs[CleanupTargetSelector::CATEGORY_CHALLENGES]);
        $this->assertNull($cutoffs[CleanupTargetSelector::CATEGORY_AUDIT]);
        $this->assertSame('2026-06-03 12:00:00', $cutoffs[CleanupTargetSelector::CATEGORY_REMINDERS]);
        $this->assertNull($cutoffs[CleanupTargetSelector::CATEGORY_SCORE_SNAPSHOTS]);
    }

    public function testInvalidNowYieldsNullCutoffs(): void
    {
        $cutoffs = $this->selector->selectCutoffs(
            'not-a-date',
            [CleanupTargetSelector::CATEGORY_CHALLENGES => 7]
        );

        $this->assertNull($cutoffs[CleanupTargetSelector::CATEGORY_CHALLENGES]);
    }

    public function testEmptyRetentionsProduceEmptyResult(): void
    {
        $this->assertSame([], $this->selector->selectCutoffs('2026-07-03 12:00:00', []));
    }
}
