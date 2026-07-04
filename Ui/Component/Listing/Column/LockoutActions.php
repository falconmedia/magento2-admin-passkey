<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Ui\Component\Listing\Column;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders the per-row unlock action for the lockouts grid.
 *
 * Only active lockouts expose an unlock link; the link carries a confirmation so
 * an admin cannot release a lockout by accident.
 */
class LockoutActions extends Column
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
            $entityId = (int) ($item[LockoutInterface::ENTITY_ID] ?? 0);
            $status = (string) ($item[LockoutInterface::STATUS] ?? '');
            if ($entityId <= 0 || $status !== LockoutInterface::STATUS_ACTIVE) {
                continue;
            }

            $item[$name]['unlock'] = [
                'href' => $this->urlBuilder->getUrl(
                    'adminpasskey/lockout/unlock',
                    ['entity_id' => $entityId]
                ),
                'label' => (string) __('Unlock'),
                'confirm' => [
                    'title' => (string) __('Release lockout'),
                    'message' => (string) __('Release this lockout so the account can log in again?'),
                ],
            ];
        }
        unset($item);

        return $dataSource;
    }
}
