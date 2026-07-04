<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\SecurityScoreSnapshot;

use FalconMedia\AdminPasskey\Model\SecurityScoreSnapshot as SecurityScoreSnapshotModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\SecurityScoreSnapshot as SecurityScoreSnapshotResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for security score snapshots.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(SecurityScoreSnapshotModel::class, SecurityScoreSnapshotResource::class);
    }
}
