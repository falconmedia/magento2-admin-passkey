<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialSearchResultsInterface;
use FalconMedia\AdminPasskey\Model\WebAuthn\CredentialDescriptors;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for building WebAuthn credential descriptors from active credentials.
 */
class CredentialDescriptorsTest extends TestCase
{
    public function testForAdminBuildsDescriptorsWithNormalizedTransports(): void
    {
        $jsonTransports = $this->credential('cred-json', '["internal","hybrid"]');
        $csvTransports = $this->credential('cred-csv', 'usb, nfc');
        $noTransports = $this->credential('cred-none', '');

        $descriptors = $this->buildService([$jsonTransports, $csvTransports, $noTransports])->forAdmin(7);

        $this->assertSame(
            [
                ['type' => 'public-key', 'id' => 'cred-json', 'transports' => ['internal', 'hybrid']],
                ['type' => 'public-key', 'id' => 'cred-csv', 'transports' => ['usb', 'nfc']],
                ['type' => 'public-key', 'id' => 'cred-none'],
            ],
            $descriptors
        );
    }

    public function testForAdminReturnsEmptyArrayWhenNoActiveCredentials(): void
    {
        $this->assertSame([], $this->buildService([])->forAdmin(7));
    }

    /**
     * @param CredentialInterface[] $items
     */
    private function buildService(array $items): CredentialDescriptors
    {
        $searchResults = $this->createMock(CredentialSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn($items);

        $repository = $this->createMock(CredentialRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('listActiveForAdmin')
            ->with(7)
            ->willReturn($searchResults);

        return new CredentialDescriptors($repository);
    }

    private function credential(string $credentialId, string $transports): CredentialInterface
    {
        $credential = $this->createMock(CredentialInterface::class);
        $credential->method('getCredentialId')->willReturn($credentialId);
        $credential->method('getTransports')->willReturn($transports);

        return $credential;
    }
}
