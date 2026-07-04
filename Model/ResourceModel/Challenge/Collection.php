<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\Challenge;

use FalconMedia\AdminPasskey\Model\Challenge as ChallengeModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\Challenge as ChallengeResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for WebAuthn challenges.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ChallengeModel::class, ChallengeResource::class);
    }
}
