<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\ChallengeRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\ChallengeSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Model\ResourceModel\Challenge as ChallengeResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\Challenge\CollectionFactory as ChallengeCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * WebAuthn challenge repository. Business logic must use this contract, never the resource model directly.
 */
class ChallengeRepository implements ChallengeRepositoryInterface
{
    public function __construct(
        private readonly ChallengeResource $resource,
        private readonly ChallengeFactory $challengeFactory,
        private readonly ChallengeCollectionFactory $collectionFactory,
        private readonly ChallengeSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(ChallengeInterface $challenge): ChallengeInterface
    {
        if (!$challenge instanceof Challenge) {
            throw new CouldNotSaveException(__('Invalid challenge entity.'));
        }
        try {
            $this->resource->save($challenge);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save challenge: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $challenge;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): ChallengeInterface
    {
        $challenge = $this->challengeFactory->create();
        $this->resource->load($challenge, $entityId);
        if ($challenge->getId() === null) {
            throw new NoSuchEntityException(__('Challenge with id "%1" does not exist.', $entityId));
        }
        return $challenge;
    }

    /**
     * @inheritdoc
     */
    public function consume(int $entityId): ChallengeInterface
    {
        $challenge = $this->getById($entityId);
        $challenge->setStatus(ChallengeInterface::STATUS_CONSUMED);
        $challenge->setConsumedAt($this->dateTime->gmtDate());

        return $this->save($challenge);
    }

    /**
     * @inheritdoc
     */
    public function deleteExpired(?string $olderThan = null): int
    {
        $threshold = $olderThan ?? $this->dateTime->gmtDate();
        try {
            $connection = $this->resource->getConnection();
            return (int) $connection->delete(
                $this->resource->getMainTable(),
                [ChallengeInterface::EXPIRES_AT . ' < ?' => $threshold]
            );
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(
                __('Could not delete expired challenges: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ChallengeSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var ChallengeSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\ChallengeInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
