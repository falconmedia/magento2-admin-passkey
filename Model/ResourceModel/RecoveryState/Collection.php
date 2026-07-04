<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\RecoveryState;

use FalconMedia\AdminPasskey\Model\RecoveryState as RecoveryStateModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\RecoveryState as RecoveryStateResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for recovery-state records.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(RecoveryStateModel::class, RecoveryStateResource::class);
    }
}
