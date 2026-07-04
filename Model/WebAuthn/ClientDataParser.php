<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Parses and validates the WebAuthn {@code clientDataJSON} structure and its
 * type / challenge / origin fields against the expected ceremony values.
 *
 * The caller is responsible for base64url-decoding {@code response.clientDataJSON}
 * to its raw bytes first, so the exact bytes hashed for signature verification and
 * the bytes parsed here are guaranteed identical.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class ClientDataParser
{
    public const TYPE_CREATE = 'webauthn.create';
    public const TYPE_GET = 'webauthn.get';

    public function __construct(
        private readonly Json $json
    ) {
    }

    /**
     * Decode raw clientDataJSON bytes into a validated associative array.
     *
     * @param string $rawClientDataJson Raw (already base64url-decoded) JSON bytes.
     * @return array{type: string, challenge: string, origin: string}
     * @throws WebAuthnVerificationException
     */
    public function parse(string $rawClientDataJson): array
    {
        if ($rawClientDataJson === '') {
            throw new WebAuthnVerificationException(__('The passkey client data is missing.'));
        }

        try {
            $data = $this->json->unserialize($rawClientDataJson);
        } catch (\Throwable $e) {
            throw new WebAuthnVerificationException(__('The passkey client data could not be read.'), $e);
        }

        if (!is_array($data)
            || !is_string($data['type'] ?? null)
            || !is_string($data['challenge'] ?? null)
            || !is_string($data['origin'] ?? null)
        ) {
            throw new WebAuthnVerificationException(__('The passkey client data is malformed.'));
        }

        return [
            'type' => $data['type'],
            'challenge' => $data['challenge'],
            'origin' => $data['origin'],
        ];
    }

    /**
     * Assert the parsed client data matches the expected ceremony parameters.
     *
     * @param array{type: string, challenge: string, origin: string} $clientData
     * @param string $expectedType One of the TYPE_* constants.
     * @param string $expectedChallenge Expected base64url challenge value.
     * @param string $expectedOrigin Expected origin (scheme://host[:port]).
     * @return void
     * @throws WebAuthnVerificationException
     */
    public function assertMatches(
        array $clientData,
        string $expectedType,
        string $expectedChallenge,
        string $expectedOrigin
    ): void {
        if (!hash_equals($expectedType, $clientData['type'])) {
            throw new WebAuthnVerificationException(__('Unexpected passkey ceremony type.'));
        }
        if (!hash_equals($expectedChallenge, $clientData['challenge'])) {
            throw new WebAuthnVerificationException(__('The passkey challenge does not match.'));
        }
        if (!hash_equals(rtrim($expectedOrigin, '/'), rtrim($clientData['origin'], '/'))) {
            throw new WebAuthnVerificationException(__('The passkey origin is not allowed.'));
        }
    }
}
