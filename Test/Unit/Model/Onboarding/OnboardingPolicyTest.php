<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\Onboarding;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialSearchResultsInterface;
use FalconMedia\AdminPasskey\Model\Config\ConfigProvider;
use FalconMedia\AdminPasskey\Model\Onboarding\OnboardingPolicy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test for the onboarding enforcement policy.
 *
 * The config accessor and credential repository are mocked, so the test asserts
 * the exemption rules (which guarantee an admin is never trapped) and the
 * requires-onboarding decision without booting a request or touching the database.
 */
class OnboardingPolicyTest extends TestCase
{
    private const ALWAYS_ALLOWED = ['adminhtml_auth_login', 'adminhtml_auth_logout'];

    /**
     * @var ConfigProvider&MockObject
     */
    private ConfigProvider&MockObject $configProvider;

    /**
     * @var CredentialRepositoryInterface&MockObject
     */
    private CredentialRepositoryInterface&MockObject $credentialRepository;

    private OnboardingPolicy $policy;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->credentialRepository = $this->createMock(CredentialRepositoryInterface::class);
        $this->policy = new OnboardingPolicy(
            $this->configProvider,
            $this->credentialRepository,
            self::ALWAYS_ALLOWED
        );
    }

    /**
     * @dataProvider exemptActionProvider
     */
    public function testIsExemptAction(string $fullActionName, bool $expected): void
    {
        $this->assertSame($expected, $this->policy->isExemptAction($fullActionName));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function exemptActionProvider(): array
    {
        return [
            'wizard is exempt' => ['adminpasskey_passkey_wizard', true],
            'register endpoint is exempt' => ['adminpasskey_register_verify', true],
            'rename endpoint is exempt' => ['adminpasskey_passkey_rename', true],
            'module route case insensitive' => ['ADMINPASSKEY_PASSKEY_INDEX', true],
            'native login is exempt' => ['adminhtml_auth_login', true],
            'native logout is exempt' => ['adminhtml_auth_logout', true],
            'dashboard is not exempt' => ['adminhtml_dashboard_index', false],
            'catalog is not exempt' => ['catalog_product_edit', false],
        ];
    }

    public function testRequiresOnboardingWhenEnabledRequiredAndNoActivePasskeys(): void
    {
        $this->configProvider->method('isEnabled')->willReturn(true);
        $this->configProvider->method('isPasskeyOnboardingRequired')->willReturn(true);
        $this->credentialRepository->method('listActiveForAdmin')
            ->with(42)
            ->willReturn($this->searchResultsWithCount(0));

        $this->assertTrue($this->policy->requiresOnboarding(42));
    }

    public function testDoesNotRequireOnboardingWhenAdminHasActivePasskey(): void
    {
        $this->configProvider->method('isEnabled')->willReturn(true);
        $this->configProvider->method('isPasskeyOnboardingRequired')->willReturn(true);
        $this->credentialRepository->method('listActiveForAdmin')
            ->with(42)
            ->willReturn($this->searchResultsWithCount(2));

        $this->assertFalse($this->policy->requiresOnboarding(42));
    }

    public function testDoesNotRequireOnboardingWhenSuiteDisabled(): void
    {
        $this->configProvider->method('isEnabled')->willReturn(false);
        $this->configProvider->expects($this->never())->method('isPasskeyOnboardingRequired');
        $this->credentialRepository->expects($this->never())->method('listActiveForAdmin');

        $this->assertFalse($this->policy->requiresOnboarding(42));
    }

    public function testDoesNotRequireOnboardingWhenPolicyOff(): void
    {
        $this->configProvider->method('isEnabled')->willReturn(true);
        $this->configProvider->method('isPasskeyOnboardingRequired')->willReturn(false);
        $this->credentialRepository->expects($this->never())->method('listActiveForAdmin');

        $this->assertFalse($this->policy->requiresOnboarding(42));
    }

    public function testDoesNotRequireOnboardingForInvalidAdminId(): void
    {
        $this->configProvider->expects($this->never())->method('isEnabled');

        $this->assertFalse($this->policy->requiresOnboarding(0));
    }

    public function testShouldRedirectIsFalseForExemptActionEvenWhenOnboardingRequired(): void
    {
        $this->configProvider->expects($this->never())->method('isEnabled');
        $this->credentialRepository->expects($this->never())->method('listActiveForAdmin');

        $this->assertFalse($this->policy->shouldRedirectToWizard(42, 'adminpasskey_passkey_wizard'));
    }

    public function testShouldRedirectIsTrueForGatedActionWhenOnboardingRequired(): void
    {
        $this->configProvider->method('isEnabled')->willReturn(true);
        $this->configProvider->method('isPasskeyOnboardingRequired')->willReturn(true);
        $this->credentialRepository->method('listActiveForAdmin')
            ->willReturn($this->searchResultsWithCount(0));

        $this->assertTrue($this->policy->shouldRedirectToWizard(42, 'adminhtml_dashboard_index'));
    }

    /**
     * Build a credential search-results stub reporting the given total count.
     *
     * @param int $count
     * @return CredentialSearchResultsInterface&MockObject
     */
    private function searchResultsWithCount(int $count): CredentialSearchResultsInterface
    {
        $results = $this->createMock(CredentialSearchResultsInterface::class);
        $results->method('getTotalCount')->willReturn($count);

        return $results;
    }
}
