<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Email;

use FalconMedia\AdminPasskey\Model\Branding\BrandingProvider;

/**
 * Assembles the set of branding template variables shared by every module email.
 *
 * Pure logic on top of {@see BrandingProvider}; the resulting array is passed to
 * TransportBuilder::setTemplateVars(). Caller-supplied variables win over the
 * branding defaults so a template can still override a shared key when needed.
 */
class BrandedEmailVariables
{
    public function __construct(
        private readonly BrandingProvider $brandingProvider
    ) {
    }

    /**
     * Build the branded template variables, merged with any extra caller vars.
     *
     * @param array<string, mixed> $extra
     * @param int|null $storeId
     * @return array<string, mixed>
     */
    public function build(array $extra = [], ?int $storeId = null): array
    {
        $branding = [
            'company_name' => $this->brandingProvider->getCompanyName($storeId),
            'support_email' => $this->brandingProvider->getSupportEmail($storeId),
            'support_url' => $this->brandingProvider->getSupportUrl($storeId),
            'documentation_url' => $this->brandingProvider->getDocumentationUrl($storeId),
            'privacy_url' => $this->brandingProvider->getPrivacyUrl($storeId),
            'footer_text' => $this->brandingProvider->getFooterText($storeId),
            'accent_color' => $this->brandingProvider->getPrimaryAccentColor($storeId),
            'secondary_accent_color' => $this->brandingProvider->getSecondaryAccentColor($storeId),
        ];

        return array_merge($branding, $extra);
    }
}
