<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Block\Adminhtml\Dashboard;

use FalconMedia\AdminPasskey\Model\Branding\BrandingProvider;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Dashboard\DashboardCard;
use FalconMedia\AdminPasskey\Model\Dashboard\DashboardCardAssembler;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Renders the FalconMedia Admin Passkey security widget on the native Magento
 * admin dashboard. The block only formats the cards assembled by the service;
 * all data assembly, config toggles and ACL checks live in the assembler.
 *
 * CSP-safe: no inline JS. Accent colours are applied via inline style attributes
 * only (permitted by the admin style-src policy).
 */
class Widget extends Template
{
    /**
     * @var DashboardCard[]|null
     */
    private ?array $cards = null;

    public function __construct(
        Context $context,
        private readonly DashboardCardAssembler $cardAssembler,
        private readonly ConfigProvider $configProvider,
        private readonly BrandingProvider $brandingProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Whether the widget should render at all (master toggle plus at least one visible card).
     *
     * @return bool
     */
    public function isWidgetEnabled(): bool
    {
        return $this->configProvider->isDashboardWidgetEnabled() && $this->getCards() !== [];
    }

    /**
     * The cards the current admin may see.
     *
     * @return DashboardCard[]
     */
    public function getCards(): array
    {
        if ($this->cards === null) {
            $this->cards = $this->cardAssembler->assemble();
        }

        return $this->cards;
    }

    /**
     * Resolve an action-link route path to a full Admin URL.
     *
     * @param string $route
     * @return string
     */
    public function getCardUrl(string $route): string
    {
        return $this->getUrl($route);
    }

    /**
     * Map a card status to a Blank/Admin severity badge CSS class.
     *
     * @param string $status
     * @return string
     */
    public function getStatusClass(string $status): string
    {
        return match ($status) {
            DashboardCard::STATUS_OK => 'grid-severity-notice',
            DashboardCard::STATUS_WARNING => 'grid-severity-minor',
            DashboardCard::STATUS_CRITICAL => 'grid-severity-critical',
            DashboardCard::STATUS_INFO => 'grid-severity-major',
            default => 'grid-severity-notice',
        };
    }

    /**
     * Branded company name for the widget heading.
     *
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->brandingProvider->getCompanyName();
    }

    /**
     * Primary accent colour (hex) for the widget.
     *
     * @return string
     */
    public function getPrimaryAccentColor(): string
    {
        return $this->brandingProvider->getPrimaryAccentColor();
    }

    /**
     * Secondary accent colour (hex) for the widget.
     *
     * @return string
     */
    public function getSecondaryAccentColor(): string
    {
        return $this->brandingProvider->getSecondaryAccentColor();
    }

    /**
     * Optional dashboard icon URL, or null when none is configured.
     *
     * @return string|null
     */
    public function getDashboardIconUrl(): ?string
    {
        return $this->brandingProvider->getDashboardIconUrl();
    }
}
