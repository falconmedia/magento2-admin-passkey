<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Endpoint;

/**
 * Pure builder for the JSON envelopes returned by the Admin passkey endpoints.
 *
 * Keeping the envelope shape in one small, dependency-free class means the login
 * and registration controllers stay thin and the contract is unit-testable
 * without booting Magento. Every response carries an explicit boolean "success"
 * flag so the browser JS never has to infer state from HTTP status alone.
 *
 * @internal Admin-only support; not part of a public web API contract.
 */
class JsonPayloadFactory
{
    /**
     * Envelope key carrying the success/failure flag.
     */
    public const KEY_SUCCESS = 'success';

    /**
     * Envelope key carrying a human-readable, non-sensitive message.
     */
    public const KEY_MESSAGE = 'message';

    /**
     * Build a success envelope, merged with any additional payload data.
     *
     * @param array<string,mixed> $data Extra payload keys (e.g. publicKey, redirectUrl).
     * @return array<string,mixed>
     */
    public function success(array $data = []): array
    {
        return [self::KEY_SUCCESS => true] + $data;
    }

    /**
     * Build a failure envelope with a generic, non-enumerating message.
     *
     * @param string $message Non-sensitive message safe to show pre-auth.
     * @param array<string,mixed> $data Optional extra payload keys.
     * @return array<string,mixed>
     */
    public function error(string $message, array $data = []): array
    {
        return [self::KEY_SUCCESS => false, self::KEY_MESSAGE => $message] + $data;
    }
}
