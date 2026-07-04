<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\ResourceModel\DiagnosticsReport;

use FalconMedia\AdminPasskey\Model\DiagnosticsReport as DiagnosticsReportModel;
use FalconMedia\AdminPasskey\Model\ResourceModel\DiagnosticsReport as DiagnosticsReportResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Collection for diagnostics reports.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(DiagnosticsReportModel::class, DiagnosticsReportResource::class);
    }
}
