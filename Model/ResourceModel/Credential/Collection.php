<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\Credential;

use FalconMedia\AdminPasskey\Model\Credential as CredentialModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\Credential as CredentialResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for passkey credentials.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(CredentialModel::class, CredentialResource::class);
    }
}
