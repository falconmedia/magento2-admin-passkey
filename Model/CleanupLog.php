<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model;

use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;
use FalconMedia\AdminPasskey\Model\ResourceModel\CleanupLog as CleanupLogResource;
use Magento\Framework\Model\AbstractModel;

/**
 * Cleanup log entity.
 */
class CleanupLog extends AbstractModel implements CleanupLogInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(CleanupLogResource::class);
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
    public function setId($id): CleanupLogInterface
    {
        return $this->setData(self::ENTITY_ID, $id === null ? null : (int) $id);
    }

    /**
     * @inheritdoc
     */
    public function getCategories(): ?string
    {
        $value = $this->getData(self::CATEGORIES);
        return $value !== null ? (string) $value : null;
    }

    /**
     * @inheritdoc
     */
    public function setCategories(?string $categories): CleanupLogInterface
    {
        return $this->setData(self::CATEGORIES, $categories);
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
    public function setCounts(?string $counts): CleanupLogInterface
    {
        return $this->setData(self::COUNTS, $counts);
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
    public function setStatus(?string $status): CleanupLogInterface
    {
        return $this->setData(self::STATUS, $status);
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
    public function setMetadata(?string $metadata): CleanupLogInterface
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
    public function setCreatedAt(?string $createdAt): CleanupLogInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
