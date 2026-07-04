<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Plugin\Backend\Authentication;

use FalconMedia\AdminPasskey\Plugin\Backend\Authentication\AllowPasskeyLoginActions;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for the pre-auth open-action whitelist decision logic.
 */
class AllowPasskeyLoginActionsTest extends TestCase
{
    private const OPEN_ACTIONS = ['adminpasskey_login_options', 'adminpasskey_login_verify'];

    private AllowPasskeyLoginActions $plugin;

    protected function setUp(): void
    {
        $this->plugin = new AllowPasskeyLoginActions(self::OPEN_ACTIONS);
    }

    /**
     * @dataProvider whitelistedActionProvider
     */
    public function testWhitelistedActionsAreOpen(string $fullActionName): void
    {
        $request = $this->createHttpRequest($fullActionName);

        $this->assertTrue($this->plugin->isOpenAction($request));
    }

    /**
     * @return array<string, array{0:string}>
     */
    public function whitelistedActionProvider(): array
    {
        return [
            'options endpoint' => ['adminpasskey_login_options'],
            'verify endpoint' => ['adminpasskey_login_verify'],
            'case insensitive' => ['ADMINPASSKEY_LOGIN_OPTIONS'],
        ];
    }

    /**
     * @dataProvider nonWhitelistedActionProvider
     */
    public function testNonWhitelistedActionsStayGated(string $fullActionName): void
    {
        $request = $this->createHttpRequest($fullActionName);

        $this->assertFalse($this->plugin->isOpenAction($request));
    }

    /**
     * @return array<string, array{0:string}>
     */
    public function nonWhitelistedActionProvider(): array
    {
        return [
            'registration options must stay behind ACL' => ['adminpasskey_register_options'],
            'registration verify must stay behind ACL' => ['adminpasskey_register_verify'],
            'audit grid must stay behind ACL' => ['adminpasskey_audit_index'],
            'unrelated admin action' => ['adminhtml_dashboard_index'],
        ];
    }

    public function testNonHttpRequestIsNeverOpen(): void
    {
        /** @var RequestInterface&MockObject $request */
        $request = $this->getMockForAbstractClass(RequestInterface::class);

        $this->assertFalse($this->plugin->isOpenAction($request));
    }

    /**
     * Build an HTTP request stub returning the given full action name.
     *
     * @param string $fullActionName
     * @return HttpRequest&MockObject
     */
    private function createHttpRequest(string $fullActionName): HttpRequest
    {
        /** @var HttpRequest&MockObject $request */
        $request = $this->createMock(HttpRequest::class);
        $request->method('getFullActionName')->willReturn($fullActionName);

        return $request;
    }
}
