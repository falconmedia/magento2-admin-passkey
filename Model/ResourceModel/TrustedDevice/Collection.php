<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\TrustedDevice;

use FalconMedia\AdminPasskey\Model\TrustedDevice as TrustedDeviceModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\TrustedDevice as TrustedDeviceResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for trusted devices.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(TrustedDeviceModel::class, TrustedDeviceResource::class);
    }
}
