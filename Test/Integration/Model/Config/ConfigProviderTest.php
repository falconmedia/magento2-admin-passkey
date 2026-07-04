<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Integration\Model\Config;

use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration test that resolves ConfigProvider from the ObjectManager and
 * asserts it reflects real saved configuration for at least one field per
 * major configuration group.
 *
 * @magentoAppIsolation enabled
 */
class ConfigProviderTest extends TestCase
{
    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    protected function setUp(): void
    {
        $this->configProvider = Bootstrap::getObjectManager()->create(ConfigProvider::class);
    }

    public function testResolvesFromObjectManager(): void
    {
        $this->assertInstanceOf(ConfigProvider::class, $this->configProvider);
    }

    /**
     * @magentoConfigFixture adminpasskey/general/enabled 0
     */
    public function testReadsBooleanFieldFromSavedConfig(): void
    {
        $this->assertFalse($this->configProvider->isEnabled());
    }

    /**
     * @magentoConfigFixture adminpasskey/authentication_policy/passkey_first_login 0
     */
    public function testReadsAuthenticationPolicyFieldFromSavedConfig(): void
    {
        $this->assertFalse($this->configProvider->isPasskeyFirstLogin());
    }

    /**
     * @magentoConfigFixture adminpasskey/trusted_devices/lifetime_days 45
     */
    public function testReadsTrustedDeviceLifetimeFromSavedConfig(): void
    {
        $this->assertSame(45, $this->configProvider->getTrustedDeviceLifetimeDays());
    }

    /**
     * @magentoConfigFixture adminpasskey/lockout/max_failed_attempts 9
     */
    public function testReadsLockoutMaxAttemptsFromSavedConfig(): void
    {
        $this->assertSame(9, $this->configProvider->getMaxFailedAttempts());
    }

    /**
     * @magentoConfigFixture adminpasskey/recovery/warning_text Integration recovery warning
     */
    public function testReadsRecoveryWarningTextFromSavedConfig(): void
    {
        $this->assertSame('Integration recovery warning', $this->configProvider->getRecoveryWarningText());
    }

    /**
     * @magentoConfigFixture adminpasskey/security_score/weight_authentication 33
     */
    public function testReadsSecurityScoreWeightFromSavedConfig(): void
    {
        $this->assertSame(33, $this->configProvider->getSecurityScoreWeightAuthentication());
    }

    /**
     * @magentoConfigFixture adminpasskey/diagnostics/support_reference_prefix ITEST
     */
    public function testReadsDiagnosticsReferencePrefixFromSavedConfig(): void
    {
        $this->assertSame('ITEST', $this->configProvider->getSupportReferencePrefix());
    }

    /**
     * @magentoConfigFixture adminpasskey/cleanup/audit_retention_days 120
     */
    public function testReadsCleanupRetentionFromSavedConfig(): void
    {
        $this->assertSame(120, $this->configProvider->getAuditRetentionDays());
    }

    /**
     * @magentoConfigFixture adminpasskey/branding/company_name Integration Co
     */
    public function testReadsBrandingCompanyNameFromSavedConfig(): void
    {
        $this->assertSame('Integration Co', $this->configProvider->getBrandingCompanyName());
    }

    /**
     * @magentoConfigFixture adminpasskey/security_dashboard_widget/card_login_ratio 0
     */
    public function testReadsDashboardCardStateFromSavedConfig(): void
    {
        $this->assertFalse($this->configProvider->isDashboardCardEnabled('login_ratio'));
    }
}
