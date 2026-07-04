<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Branding;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolves white-label branding values, applying FalconMedia defaults whenever
 * branding is disabled or an individual value is left empty.
 *
 * Fallback contract (identical for every scalar value):
 *  - branding disabled            -> FalconMedia default
 *  - branding enabled, value empty -> FalconMedia default
 *  - branding enabled, value set   -> configured value
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class BrandingProvider
{
    public const DEFAULT_COMPANY_NAME = 'FalconMedia';
    public const DEFAULT_SUPPORT_EMAIL = 'support@falconmedia.nl';
    public const DEFAULT_SUPPORT_URL = 'https://falconmedia.nl/support';
    public const DEFAULT_DOCUMENTATION_URL = 'https://falconmedia.nl/docs/admin-passkey';
    public const DEFAULT_PRIVACY_URL = 'https://falconmedia.nl/privacy';
    public const DEFAULT_FOOTER_TEXT = 'Secured by FalconMedia Admin Passkey';
    public const DEFAULT_PRIMARY_COLOR = '#f26322';
    public const DEFAULT_SECONDARY_COLOR = '#2563eb';

    /**
     * Media sub-directory (relative to pub/media) that stores branding assets.
     */
    private const MEDIA_BASE_DIR = 'adminpasskey';

    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Whether white-label branding is enabled.
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->configProvider->isBrandingEnabled($storeId);
    }

    public function getCompanyName(?int $storeId = null): string
    {
        return $this->resolve($this->configProvider->getBrandingCompanyName($storeId), self::DEFAULT_COMPANY_NAME, $storeId);
    }

    public function getSupportEmail(?int $storeId = null): string
    {
        return $this->resolve($this->configProvider->getBrandingSupportEmail($storeId), self::DEFAULT_SUPPORT_EMAIL, $storeId);
    }

    public function getSupportUrl(?int $storeId = null): string
    {
        return $this->resolve($this->configProvider->getBrandingSupportUrl($storeId), self::DEFAULT_SUPPORT_URL, $storeId);
    }

    public function getDocumentationUrl(?int $storeId = null): string
    {
        return $this->resolve(
            $this->configProvider->getBrandingDocumentationUrl($storeId),
            self::DEFAULT_DOCUMENTATION_URL,
            $storeId
        );
    }

    public function getPrivacyUrl(?int $storeId = null): string
    {
        return $this->resolve($this->configProvider->getBrandingPrivacyUrl($storeId), self::DEFAULT_PRIVACY_URL, $storeId);
    }

    public function getFooterText(?int $storeId = null): string
    {
        return $this->resolve($this->configProvider->getBrandingFooterText($storeId), self::DEFAULT_FOOTER_TEXT, $storeId);
    }

    public function getPrimaryAccentColor(?int $storeId = null): string
    {
        return $this->resolve(
            $this->configProvider->getBrandingPrimaryAccentColor($storeId),
            self::DEFAULT_PRIMARY_COLOR,
            $storeId
        );
    }

    public function getSecondaryAccentColor(?int $storeId = null): string
    {
        return $this->resolve(
            $this->configProvider->getBrandingSecondaryAccentColor($storeId),
            self::DEFAULT_SECONDARY_COLOR,
            $storeId
        );
    }

    public function getLoginIntroText(?int $storeId = null): string
    {
        return $this->resolve(
            $this->configProvider->getBrandingLoginIntroText($storeId),
            (string) __('Sign in securely with your passkey — no password required.'),
            $storeId
        );
    }

    public function getWizardIntroText(?int $storeId = null): string
    {
        return $this->resolve(
            $this->configProvider->getBrandingWizardIntroText($storeId),
            (string) __('Set up a passkey to protect your Admin account.'),
            $storeId
        );
    }

    public function getDiagnosticsIntroText(?int $storeId = null): string
    {
        return $this->resolve(
            $this->configProvider->getBrandingDiagnosticsIntroText($storeId),
            (string) __('Generate a sanitised diagnostics bundle to share with support.'),
            $storeId
        );
    }

    /**
     * Human-readable security-score labels keyed by rating band.
     *
     * @return array<string, string>
     */
    public function getScoreLabels(?int $storeId = null): array
    {
        return [
            'poor' => $this->resolve(
                $this->configProvider->getBrandingScoreLabelPoor($storeId),
                (string) __('Poor'),
                $storeId
            ),
            'fair' => $this->resolve(
                $this->configProvider->getBrandingScoreLabelFair($storeId),
                (string) __('Fair'),
                $storeId
            ),
            'good' => $this->resolve(
                $this->configProvider->getBrandingScoreLabelGood($storeId),
                (string) __('Good'),
                $storeId
            ),
            'excellent' => $this->resolve(
                $this->configProvider->getBrandingScoreLabelExcellent($storeId),
                (string) __('Excellent'),
                $storeId
            ),
        ];
    }

    /**
     * Public media URL of the branded logo, or null when none is configured.
     */
    public function getLogoUrl(?int $storeId = null): ?string
    {
        return $this->mediaUrl('logo', $this->configProvider->getBrandingLogo($storeId), $storeId);
    }

    /**
     * Public media URL of the branded icon, or null when none is configured.
     */
    public function getIconUrl(?int $storeId = null): ?string
    {
        return $this->mediaUrl('icon', $this->configProvider->getBrandingIcon($storeId), $storeId);
    }

    /**
     * Public media URL of the login background image, or null when none is configured.
     */
    public function getBackgroundImageUrl(?int $storeId = null): ?string
    {
        return $this->mediaUrl('background_image', $this->configProvider->getBrandingBackgroundImage($storeId), $storeId);
    }

    /**
     * Public media URL of the wizard illustration, or null when none is configured.
     */
    public function getWizardIllustrationUrl(?int $storeId = null): ?string
    {
        return $this->mediaUrl(
            'wizard_illustration',
            $this->configProvider->getBrandingWizardIllustration($storeId),
            $storeId
        );
    }

    /**
     * Public media URL of the dashboard icon, or null when none is configured.
     */
    public function getDashboardIconUrl(?int $storeId = null): ?string
    {
        return $this->mediaUrl('dashboard_icon', $this->configProvider->getBrandingDashboardIcon($storeId), $storeId);
    }

    /**
     * Apply the shared fallback contract to a single scalar value.
     */
    private function resolve(string $configured, string $default, ?int $storeId): string
    {
        if (!$this->configProvider->isBrandingEnabled($storeId)) {
            return $default;
        }

        return $configured !== '' ? $configured : $default;
    }

    /**
     * Build the public media URL for a stored branding asset, honouring branding state.
     *
     * @param string $subDir
     * @param string $file
     * @param int|null $storeId
     * @return string|null
     */
    private function mediaUrl(string $subDir, string $file, ?int $storeId): ?string
    {
        if (!$this->configProvider->isBrandingEnabled($storeId) || $file === '') {
            return null;
        }

        try {
            $store = $this->storeManager->getStore($storeId);
            if (!$store instanceof Store) {
                return null;
            }
            $base = rtrim($store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/');
        } catch (\Throwable) {
            return null;
        }

        return $base . '/' . self::MEDIA_BASE_DIR . '/' . $subDir . '/' . ltrim($file, '/');
    }
}
