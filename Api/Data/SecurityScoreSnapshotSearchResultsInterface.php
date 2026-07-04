<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results for security score snapshots.
 */
interface SecurityScoreSnapshotSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get security score snapshots.
     *
     * @return SecurityScoreSnapshotInterface[]
     */
    public function getItems(): array;

    /**
     * Set security score snapshots.
     *
     * @param SecurityScoreSnapshotInterface[] $items
     * @return SecurityScoreSnapshotSearchResultsInterface
     */
    public function setItems(array $items): SecurityScoreSnapshotSearchResultsInterface;
}
