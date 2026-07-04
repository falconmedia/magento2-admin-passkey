<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SecurityScore;

use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotInterface;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Security score engine.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
interface SecurityScoreServiceInterface
{
    /**
     * Compute the current security score without persisting it.
     *
     * @return SecurityScoreResult
     */
    public function compute(): SecurityScoreResult;

    /**
     * Compute the current score and persist it as a snapshot (audited).
     *
     * @return SecurityScoreSnapshotInterface
     * @throws CouldNotSaveException
     */
    public function snapshot(): SecurityScoreSnapshotInterface;

    /**
     * The most recent persisted snapshot, or null when none exists.
     *
     * @return SecurityScoreSnapshotInterface|null
     */
    public function getCurrent(): ?SecurityScoreSnapshotInterface;

    /**
     * Recent snapshots ordered newest first.
     *
     * @param int $limit
     * @return SecurityScoreSnapshotInterface[]
     */
    public function getHistory(int $limit = 20): array;
}
