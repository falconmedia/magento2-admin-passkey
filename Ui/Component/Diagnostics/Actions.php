<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Ui\Component\Diagnostics;

use FalconMedia\AdminPasskey\Api\Data\DiagnosticsReportInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Row actions for the diagnostics grid. Only the read-only Download link is a
 * per-row action; sending is a POST mass action for CSRF safety.
 */
class Actions extends Column
{
    /**
     * URL path to the download controller.
     */
    private const URL_DOWNLOAD = 'adminpasskey/diagnostics/download';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Add the download action to each generated report row.
     *
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $id = (int) ($item['entity_id'] ?? 0);
            $status = (string) ($item['status'] ?? '');
            if ($id > 0 && $status !== DiagnosticsReportInterface::STATUS_PENDING
                && $status !== DiagnosticsReportInterface::STATUS_FAILED
            ) {
                $item[$name]['download'] = [
                    'href' => $this->urlBuilder->getUrl(self::URL_DOWNLOAD, ['id' => $id]),
                    'label' => __('Download'),
                ];
            }
        }
        unset($item);

        return $dataSource;
    }
}
