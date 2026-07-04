<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results for cleanup logs.
 */
interface CleanupLogSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get cleanup logs.
     *
     * @return CleanupLogInterface[]
     */
    public function getItems(): array;

    /**
     * Set cleanup logs.
     *
     * @param CleanupLogInterface[] $items
     * @return CleanupLogSearchResultsInterface
     */
    public function setItems(array $items): CleanupLogSearchResultsInterface;
}
