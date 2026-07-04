<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results interface for WebAuthn challenges.
 */
interface ChallengeSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get challenges list.
     *
     * @return ChallengeInterface[]
     */
    public function getItems(): array;

    /**
     * Set challenges list.
     *
     * @param ChallengeInterface[] $items
     * @return ChallengeSearchResultsInterface
     */
    public function setItems(array $items): ChallengeSearchResultsInterface;
}
