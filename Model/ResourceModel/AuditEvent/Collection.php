<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\AuditEvent;

use FalconMedia\AdminPasskey\Model\AuditEvent as AuditEventModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\AuditEvent as AuditEventResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for audit events.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(AuditEventModel::class, AuditEventResource::class);
    }
}
