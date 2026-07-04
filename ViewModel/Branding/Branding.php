<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\ViewModel\Branding;

use FalconMedia\AdminPasskey\Model\Branding\BrandingProvider;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * View model exposing white-label branding to admin templates (login, wizard,
 * dashboard, diagnostics). Delegates all fallback logic to BrandingProvider so
 * templates only format already-resolved values.
 */
class Branding implements ArgumentInterface
{
    public function __construct(
        private readonly BrandingProvider $brandingProvider
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->brandingProvider->isEnabled();
    }

    public function getCompanyName(): string
    {
        return $this->brandingProvider->getCompanyName();
    }

    public function getSupportEmail(): string
    {
        return $this->brandingProvider->getSupportEmail();
    }

    public function getSupportUrl(): string
    {
        return $this->brandingProvider->getSupportUrl();
    }

    public function getDocumentationUrl(): string
    {
        return $this->brandingProvider->getDocumentationUrl();
    }

    public function getPrivacyUrl(): string
    {
        return $this->brandingProvider->getPrivacyUrl();
    }

    public function getFooterText(): string
    {
        return $this->brandingProvider->getFooterText();
    }

    public function getPrimaryAccentColor(): string
    {
        return $this->brandingProvider->getPrimaryAccentColor();
    }

    public function getSecondaryAccentColor(): string
    {
        return $this->brandingProvider->getSecondaryAccentColor();
    }

    public function getLoginIntroText(): string
    {
        return $this->brandingProvider->getLoginIntroText();
    }

    public function getWizardIntroText(): string
    {
        return $this->brandingProvider->getWizardIntroText();
    }

    public function getDiagnosticsIntroText(): string
    {
        return $this->brandingProvider->getDiagnosticsIntroText();
    }

    public function getLogoUrl(): ?string
    {
        return $this->brandingProvider->getLogoUrl();
    }

    public function getIconUrl(): ?string
    {
        return $this->brandingProvider->getIconUrl();
    }

    public function getBackgroundImageUrl(): ?string
    {
        return $this->brandingProvider->getBackgroundImageUrl();
    }

    public function getWizardIllustrationUrl(): ?string
    {
        return $this->brandingProvider->getWizardIllustrationUrl();
    }

    public function getDashboardIconUrl(): ?string
    {
        return $this->brandingProvider->getDashboardIconUrl();
    }
}
