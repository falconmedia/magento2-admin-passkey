<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\Lockout;

use FalconMedia\AdminPasskey\Model\Lockout as LockoutModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\Lockout as LockoutResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for lockouts.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(LockoutModel::class, LockoutResource::class);
    }
}
