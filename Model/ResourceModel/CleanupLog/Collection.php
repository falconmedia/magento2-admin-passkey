<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\CleanupLog;

use FalconMedia\AdminPasskey\Model\CleanupLog as CleanupLogModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\CleanupLog as CleanupLogResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for cleanup logs.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(CleanupLogModel::class, CleanupLogResource::class);
    }
}
