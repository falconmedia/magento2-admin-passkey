<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results interface for lockouts.
 */
interface LockoutSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get lockouts list.
     *
     * @return LockoutInterface[]
     */
    public function getItems(): array;

    /**
     * Set lockouts list.
     *
     * @param LockoutInterface[] $items
     * @return LockoutSearchResultsInterface
     */
    public function setItems(array $items): LockoutSearchResultsInterface;
}
