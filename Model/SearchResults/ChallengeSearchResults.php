<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\SearchResults;

use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

/**
 * Concrete search-results container for challenges.
 *
 * Re-declares the typed item accessors so an instance genuinely satisfies the
 * module's strongly typed repository contract.
 */
class ChallengeSearchResults extends SearchResults implements ChallengeSearchResultsInterface
{
    /**
     * @return ChallengeInterface[]
     */
    public function getItems(): array
    {
        /** @var ChallengeInterface[] $items */
        $items = parent::getItems();

        return $items;
    }

    /**
     * @param ChallengeInterface[] $items
     * @return ChallengeSearchResultsInterface
     */
    public function setItems(array $items): ChallengeSearchResultsInterface
    {
        /** @var array<int, \Magento\Framework\Api\AbstractExtensibleObject> $extensibleItems */
        $extensibleItems = $items;
        parent::setItems($extensibleItems);

        return $this;
    }
}
