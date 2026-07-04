<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Api\CredentialRepositoryInterface;
use FalconMedia\AdminPasskey\Api\Data\CredentialInterface;

/**
 * Builds WebAuthn PublicKeyCredentialDescriptor entries from an admin user's
 * active credentials. Used for excludeCredentials (registration) and
 * allowCredentials (assertion).
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class CredentialDescriptors
{
    public function __construct(
        private readonly CredentialRepositoryInterface $credentialRepository
    ) {
    }

    /**
     * Descriptor list for every active credential of the given admin user.
     *
     * @param int $adminUserId
     * @return array<int, array<string, mixed>>
     */
    public function forAdmin(int $adminUserId): array
    {
        $descriptors = [];
        foreach ($this->credentialRepository->listActiveForAdmin($adminUserId)->getItems() as $credential) {
            $descriptor = [
                'type' => 'public-key',
                'id' => (string) $credential->getCredentialId(),
            ];

            $transports = $this->normalizeTransports($credential);
            if ($transports !== []) {
                $descriptor['transports'] = $transports;
            }

            $descriptors[] = $descriptor;
        }

        return $descriptors;
    }

    /**
     * Normalize the stored transports column into a list of transport strings.
     *
     * Accepts a JSON array, a comma-separated list, or an empty value.
     *
     * @param CredentialInterface $credential
     * @return array<int, string>
     */
    private function normalizeTransports(CredentialInterface $credential): array
    {
        $raw = (string) $credential->getTransports();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded), static fn (string $t): bool => $t !== ''));
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $t): bool => $t !== ''));
    }
}
