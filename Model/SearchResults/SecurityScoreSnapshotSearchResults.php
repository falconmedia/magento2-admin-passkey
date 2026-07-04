<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotInterface;
use FalconMedia\AdminPasskey\Api\Data\SecurityScoreSnapshotSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for security-score snapshots.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract.
 */
class SecurityScoreSnapshotSearchResults extends SearchResults implements SecurityScoreSnapshotSearchResultsInterface
{
    /**
     * @return SecurityScoreSnapshotInterface[]
     */
    public function getItems(): array
    {
        /** @var SecurityScoreSnapshotInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param SecurityScoreSnapshotInterface[] $items
     * @return SecurityScoreSnapshotSearchResultsInterface
     */
    public function setItems(array $items): SecurityScoreSnapshotSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
