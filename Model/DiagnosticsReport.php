<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\DiagnosticsReport as DiagnosticsReportResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Diagnostics report entity.
 */
class DiagnosticsReport extends AbstractModel implements DiagnosticsReportInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(DiagnosticsReportResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        $value = $this->getData(self::ENTITY_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setId($id): DiagnosticsReportInterface
    {
        return $this->setData(self::ENTITY_ID, $id === null ? null : (int) $id);
    }

    /**
     * @inheritdoc
     */
    public function getSupportReferenceId(): ?string
    {
        $value = $this->getData(self::SUPPORT_REFERENCE_ID);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setSupportReferenceId(?string $supportReferenceId): DiagnosticsReportInterface
    {
        return $this->setData(self::SUPPORT_REFERENCE_ID, $supportReferenceId);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        $value = $this->getData(self::STATUS);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?string $status): DiagnosticsReportInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getFiles(): ?string
    {
        $value = $this->getData(self::FILES);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setFiles(?string $files): DiagnosticsReportInterface
    {
        return $this->setData(self::FILES, $files);
    }

    /**
     * @inheritdoc
     */
    public function getCounts(): ?string
    {
        $value = $this->getData(self::COUNTS);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setCounts(?string $counts): DiagnosticsReportInterface
    {
        return $this->setData(self::COUNTS, $counts);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata(): ?string
    {
        $value = $this->getData(self::METADATA);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setMetadata(?string $metadata): DiagnosticsReportInterface
    {
        return $this->setData(self::METADATA, $metadata);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): DiagnosticsReportInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(?string $updatedAt): DiagnosticsReportInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
