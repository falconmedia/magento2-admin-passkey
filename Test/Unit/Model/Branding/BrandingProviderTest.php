<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Branding;

use FalconMedia\AdminPasskey\Model\Branding\BrandingProvider;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the branding fallback contract and media URL building.
 */
class BrandingProviderTest extends TestCase
{
    /**
     * @var ConfigProvider&MockObject
     */
    private ConfigProvider&MockObject $configProvider;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface&MockObject $storeManager;

    private BrandingProvider $provider;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->provider = new BrandingProvider($this->configProvider, $this->storeManager);
    }

    public function testCompanyNameFallsBackToDefaultWhenBrandingDisabled(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(false);
        $this->configProvider->method('getBrandingCompanyName')->willReturn('Acme BV');

        $this->assertSame(BrandingProvider::DEFAULT_COMPANY_NAME, $this->provider->getCompanyName());
    }

    public function testCompanyNameFallsBackToDefaultWhenEnabledButEmpty(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(true);
        $this->configProvider->method('getBrandingCompanyName')->willReturn('');

        $this->assertSame(BrandingProvider::DEFAULT_COMPANY_NAME, $this->provider->getCompanyName());
    }

    public function testCompanyNameUsesConfiguredValueWhenEnabledAndSet(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(true);
        $this->configProvider->method('getBrandingCompanyName')->willReturn('Acme BV');

        $this->assertSame('Acme BV', $this->provider->getCompanyName());
    }

    public function testAccentColorFallsBackToDefault(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(true);
        $this->configProvider->method('getBrandingPrimaryAccentColor')->willReturn('');

        $this->assertSame(BrandingProvider::DEFAULT_PRIMARY_COLOR, $this->provider->getPrimaryAccentColor());
    }

    public function testScoreLabelsFallBackToDefaultsWhenDisabled(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(false);

        $labels = $this->provider->getScoreLabels();

        $this->assertSame('Poor', $labels['poor']);
        $this->assertSame('Excellent', $labels['excellent']);
    }

    public function testScoreLabelsUseConfiguredValuesWhenSet(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(true);
        $this->configProvider->method('getBrandingScoreLabelPoor')->willReturn('Weak');
        $this->configProvider->method('getBrandingScoreLabelFair')->willReturn('OK');
        $this->configProvider->method('getBrandingScoreLabelGood')->willReturn('Solid');
        $this->configProvider->method('getBrandingScoreLabelExcellent')->willReturn('Top');

        $labels = $this->provider->getScoreLabels();

        $this->assertSame('Weak', $labels['poor']);
        $this->assertSame('Top', $labels['excellent']);
    }

    public function testLogoUrlReturnsNullWhenBrandingDisabled(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(false);
        $this->configProvider->method('getBrandingLogo')->willReturn('logo.png');

        $this->assertNull($this->provider->getLogoUrl());
    }

    public function testLogoUrlReturnsNullWhenNoFileConfigured(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(true);
        $this->configProvider->method('getBrandingLogo')->willReturn('');

        $this->assertNull($this->provider->getLogoUrl());
    }

    public function testLogoUrlIsBuiltFromMediaBaseUrl(): void
    {
        $this->configProvider->method('isBrandingEnabled')->willReturn(true);
        $this->configProvider->method('getBrandingLogo')->willReturn('logo.png');

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->with(UrlInterface::URL_TYPE_MEDIA)->willReturn('https://shop.test/media/');
        $this->storeManager->method('getStore')->willReturn($store);

        $this->assertSame('https://shop.test/media/adminpasskey/logo/logo.png', $this->provider->getLogoUrl());
    }
}
