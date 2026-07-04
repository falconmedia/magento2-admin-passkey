<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Ui\Component\Migration;

use FalconMedia\AdminPasskey\Model\Migration\MigrationRowAssembler;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as CoreDataProvider;

/**
 * Migration dashboard data provider.
 *
 * Wraps the native admin_user grid (so filtering, sorting, paging and CSV export
 * keep working on the real user columns) and enriches every visible row with the
 * computed passkey-migration columns via {@see MigrationRowAssembler}.
 */
class DataProvider extends CoreDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param MigrationRowAssembler $rowAssembler
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        private readonly MigrationRowAssembler $rowAssembler,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    /**
     * Enrich the native admin-user rows with computed migration columns.
     *
     * @return array<string, mixed>
     */
    public function getData()
    {
        $data = parent::getData();
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $key => $row) {
                if (is_array($row)) {
                    $data['items'][$key] = $this->rowAssembler->assembleRow($row);
                }
            }
        }

        return $data;
    }
}
