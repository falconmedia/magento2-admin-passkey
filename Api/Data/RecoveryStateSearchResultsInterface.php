<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results for recovery-state records.
 */
interface RecoveryStateSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get recovery-state records.
     *
     * @return RecoveryStateInterface[]
     */
    public function getItems(): array;

    /**
     * Set recovery-state records.
     *
     * @param RecoveryStateInterface[] $items
     * @return RecoveryStateSearchResultsInterface
     */
    public function setItems(array $items): RecoveryStateSearchResultsInterface;
}
