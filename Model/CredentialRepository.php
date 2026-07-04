<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\AuditLoggerInterface;
use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialSearchResultsInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialSearchResultsInterfaceFactory;
use FalconMedia\AdminPasskey\Model\ResourceModel\Credential as CredentialResource;
use FalconMedia\AdminPasskey\Model\ResourceModel\Credential\CollectionFactory as CredentialCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Passkey credential repository. Business logic must use this contract, never the resource model directly.
 */
class CredentialRepository implements CredentialRepositoryInterface
{
    public function __construct(
        private readonly CredentialResource $resource,
        private readonly CredentialFactory $credentialFactory,
        private readonly CredentialCollectionFactory $collectionFactory,
        private readonly CredentialSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly DateTime $dateTime,
        private readonly AuditLoggerInterface $auditLogger,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(CredentialInterface $credential): CredentialInterface
    {
        if (!$credential instanceof Credential) {
            throw new CouldNotSaveException(__('Invalid credential entity.'));
        }
        try {
            $this->resource->save($credential);
        } catch (\Throwable $e) {
            throw new CouldNotSaveException(
                __('Could not save passkey credential: %1', $e->getMessage()),
                $e instanceof \Exception ? $e : null
            );
        }
        return $credential;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): CredentialInterface
    {
        $credential = $this->credentialFactory->create();
        $this->resource->load($credential, $entityId);
        if ($credential->getId() === null) {
            throw new NoSuchEntityException(__('Passkey credential with id "%1" does not exist.', $entityId));
        }
        return $credential;
    }

    /**
     * @inheritdoc
     */
    public function getByCredentialId(string $credentialId): CredentialInterface
    {
        $credential = $this->credentialFactory->create();
        $this->resource->load($credential, $credentialId, CredentialInterface::CREDENTIAL_ID);
        if ($credential->getId() === null) {
            throw new NoSuchEntityException(
                __('Passkey credential with credential id "%1" does not exist.', $credentialId)
            );
        }
        return $credential;
    }

    /**
     * @inheritdoc
     */
    public function listActiveForAdmin(int $adminUserId): CredentialSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(CredentialInterface::ADMIN_USER_ID, $adminUserId)
            ->addFilter(CredentialInterface::STATUS, CredentialInterface::STATUS_ACTIVE)
            ->create();

        return $this->getList($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function revoke(int $entityId): CredentialInterface
    {
        $credential = $this->getById($entityId);
        $credential->setStatus(CredentialInterface::STATUS_REVOKED);
        $credential->setRevokedAt($this->dateTime->gmtDate());

        $saved = $this->save($credential);
        $this->recordRevokeAudit($saved);

        return $saved;
    }

    /**
     * Record an audit event for a credential revoke. Audit failures must never
     * break the revoke itself, so any error is logged and swallowed.
     *
     * @param CredentialInterface $credential
     * @return void
     */
    private function recordRevokeAudit(CredentialInterface $credential): void
    {
        try {
            $this->auditLogger->record(
                AuditLoggerInterface::EVENT_PASSKEY_REVOKE,
                [
                    AuditLoggerInterface::CONTEXT_TARGET => $credential->getAdminUserId(),
                    AuditLoggerInterface::CONTEXT_METADATA => [
                        'credential_row_id' => $credential->getId(),
                        'friendly_name' => $credential->getFriendlyName(),
                    ],
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Failed to record audit event for passkey credential revoke: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CredentialSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var CredentialSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var \FalconMedia\AdminPasskey\Api\Data\CredentialInterface[] $items */
        $items = $collection->getItems();
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
