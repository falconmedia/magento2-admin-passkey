<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Ui\Component\Listing\Column;

use FalconMedia\AdminPasskey\Api\Data\TrustedDeviceInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders the per-row revoke action for the trusted-devices grid.
 *
 * Only active devices expose a revoke link; the link carries a confirmation so an
 * admin cannot revoke a device by accident.
 */
class TrustedDeviceActions extends Column
{
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
     * @inheritdoc
     *
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items']) || !is_array($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $entityId = (int) ($item[TrustedDeviceInterface::ENTITY_ID] ?? 0);
            $status = (string) ($item[TrustedDeviceInterface::STATUS] ?? '');
            if ($entityId <= 0 || $status !== TrustedDeviceInterface::STATUS_ACTIVE) {
                continue;
            }

            $item[$name]['revoke'] = [
                'href' => $this->urlBuilder->getUrl(
                    'adminpasskey/trusteddevice/revoke',
                    ['entity_id' => $entityId]
                ),
                'label' => (string) __('Revoke'),
                'confirm' => [
                    'title' => (string) __('Revoke trusted device'),
                    'message' => (string) __('Revoke this trusted device? The browser will need to be trusted again.'),
                ],
            ];
        }
        unset($item);

        return $dataSource;
    }
}
