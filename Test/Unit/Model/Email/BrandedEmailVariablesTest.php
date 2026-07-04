<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Email;

use FalconMedia\AdminPasskey\Model\Branding\BrandingProvider;
use FalconMedia\AdminPasskey\Model\Email\BrandedEmailVariables;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the branded email variable assembler.
 */
class BrandedEmailVariablesTest extends TestCase
{
    /**
     * @var BrandingProvider&MockObject
     */
    private BrandingProvider&MockObject $brandingProvider;

    private BrandedEmailVariables $variables;

    protected function setUp(): void
    {
        $this->brandingProvider = $this->createMock(BrandingProvider::class);
        $this->brandingProvider->method('getCompanyName')->willReturn('Acme BV');
        $this->brandingProvider->method('getSupportEmail')->willReturn('help@acme.test');
        $this->brandingProvider->method('getSupportUrl')->willReturn('https://acme.test/support');
        $this->brandingProvider->method('getDocumentationUrl')->willReturn('https://acme.test/docs');
        $this->brandingProvider->method('getPrivacyUrl')->willReturn('https://acme.test/privacy');
        $this->brandingProvider->method('getFooterText')->willReturn('Secured by Acme');
        $this->brandingProvider->method('getPrimaryAccentColor')->willReturn('#111111');
        $this->brandingProvider->method('getSecondaryAccentColor')->willReturn('#222222');

        $this->variables = new BrandedEmailVariables($this->brandingProvider);
    }

    public function testBuildReturnsAllBrandingVariables(): void
    {
        $vars = $this->variables->build();

        $this->assertSame('Acme BV', $vars['company_name']);
        $this->assertSame('help@acme.test', $vars['support_email']);
        $this->assertSame('https://acme.test/support', $vars['support_url']);
        $this->assertSame('https://acme.test/docs', $vars['documentation_url']);
        $this->assertSame('https://acme.test/privacy', $vars['privacy_url']);
        $this->assertSame('Secured by Acme', $vars['footer_text']);
        $this->assertSame('#111111', $vars['accent_color']);
        $this->assertSame('#222222', $vars['secondary_accent_color']);
    }

    public function testExtraVariablesAreMergedIn(): void
    {
        $vars = $this->variables->build(['admin_name' => 'Jane', 'locked_until' => '2026-01-01 00:00:00']);

        $this->assertSame('Jane', $vars['admin_name']);
        $this->assertSame('2026-01-01 00:00:00', $vars['locked_until']);
        $this->assertSame('Acme BV', $vars['company_name']);
    }

    public function testExtraVariablesOverrideBrandingDefaults(): void
    {
        $vars = $this->variables->build(['company_name' => 'Override Ltd']);

        $this->assertSame('Override Ltd', $vars['company_name']);
    }
}
