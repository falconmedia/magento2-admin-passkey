<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use CBOR\CBOREncoder;
use CBOR\Types\CBORByteString;
use FalconMedia\AdminPasskey\Model\WebAuthn\AuthenticatorData;
use FalconMedia\AdminPasskey\Model\WebAuthn\Base64UrlEncoder;
use OpenSSLAsymmetricKey;

/**
 * Deterministic WebAuthn test-vector generator.
 *
 * Creates a real EC P-256 (ES256) or RSA (RS256) key pair with openssl, then
 * hand-crafts authenticatorData, clientDataJSON, the attestation object and a
 * genuine signature so the verification services can be exercised end-to-end
 * without a browser. This is a test support class (no {@code Test} suffix), so
 * PHPUnit does not collect it as a test case.
 *
 * @internal Test support only.
 */
class WebAuthnTestVectors
{
    private const AAGUID = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

    private Base64UrlEncoder $encoder;
    private OpenSSLAsymmetricKey $privateKey;
    private string $coseKey;
    private string $credentialIdRaw;

    public function __construct(
        private readonly string $rpId = 'example.com',
        private readonly string $origin = 'https://example.com',
        private readonly int $adminUserId = 42,
        string $algorithm = 'es256'
    ) {
        $this->encoder = new Base64UrlEncoder();
        $this->credentialIdRaw = random_bytes(32);
        if ($algorithm === 'rs256') {
            $this->initRsa();
        } else {
            $this->initEc();
        }
    }

    /**
     * Base64url credential id used across the crafted vectors.
     *
     * @return string
     */
    public function getCredentialId(): string
    {
        return $this->encoder->encode($this->credentialIdRaw);
    }

    /**
     * Raw CBOR-encoded COSE public key (as stored in the credential row, decoded).
     *
     * @return string
     */
    public function getCoseKey(): string
    {
        return $this->coseKey;
    }

    /**
     * Base64url COSE public key (as persisted in Credential::public_key).
     *
     * @return string
     */
    public function getEncodedCoseKey(): string
    {
        return $this->encoder->encode($this->coseKey);
    }

    /**
     * Admin user id bound to the vectors.
     *
     * @return int
     */
    public function getAdminUserId(): int
    {
        return $this->adminUserId;
    }

    /**
     * Build a registration (attestation) response for the given challenge.
     *
     * Supported options: signCount(int), uv(bool), origin(string), type(string),
     * rpId(string, for rpIdHash), credentialId(string base64url), transports(array),
     * fmt(string), omitAttestedData(bool).
     *
     * @param string $challenge Base64url challenge value.
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function registration(string $challenge, array $options = []): array
    {
        $type = (string) ($options['type'] ?? 'webauthn.create');
        $origin = (string) ($options['origin'] ?? $this->origin);
        $signCount = (int) ($options['signCount'] ?? 0);
        $uv = (bool) ($options['uv'] ?? true);
        $rpId = (string) ($options['rpId'] ?? $this->rpId);
        $credentialId = (string) ($options['credentialId'] ?? $this->getCredentialId());
        $fmt = (string) ($options['fmt'] ?? 'none');

        $authData = $this->registrationAuthData($signCount, $uv, $rpId, (bool) ($options['omitAttestedData'] ?? false));
        $attestationObject = CBOREncoder::encode([
            'fmt' => $fmt,
            'attStmt' => [],
            'authData' => new CBORByteString($authData),
        ]);

        return [
            'id' => $credentialId,
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->encoder->encode($this->clientDataJson($type, $challenge, $origin)),
                'attestationObject' => $this->encoder->encode((string) $attestationObject),
                'transports' => $options['transports'] ?? ['internal', 'hybrid'],
            ],
        ];
    }

    /**
     * Build an assertion (authentication) response for the given challenge.
     *
     * Supported options: signCount(int), uv(bool), origin(string), type(string),
     * rpId(string, for rpIdHash), credentialId(string base64url), userHandle(?string
     * raw handle; use false to omit), tamperSignature(bool), signWith(OpenSSLAsymmetricKey).
     *
     * @param string $challenge Base64url challenge value.
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function assertion(string $challenge, array $options = []): array
    {
        $type = (string) ($options['type'] ?? 'webauthn.get');
        $origin = (string) ($options['origin'] ?? $this->origin);
        $signCount = (int) ($options['signCount'] ?? 1);
        $uv = (bool) ($options['uv'] ?? true);
        $rpId = (string) ($options['rpId'] ?? $this->rpId);
        $credentialId = (string) ($options['credentialId'] ?? $this->getCredentialId());
        $signWith = $options['signWith'] ?? $this->privateKey;

        $rawClientData = $this->clientDataJson($type, $challenge, $origin);
        $authData = $this->assertionAuthData($signCount, $uv, $rpId);
        $signedData = $authData . hash('sha256', $rawClientData, true);

        $signature = '';
        openssl_sign($signedData, $signature, $signWith, OPENSSL_ALGO_SHA256);
        if (!empty($options['tamperSignature'])) {
            $signature[0] = $signature[0] === "\x01" ? "\x02" : "\x01";
        }

        $response = [
            'clientDataJSON' => $this->encoder->encode($rawClientData),
            'authenticatorData' => $this->encoder->encode($authData),
            'signature' => $this->encoder->encode($signature),
        ];
        $userHandle = $options['userHandle'] ?? (string) $this->adminUserId;
        if ($userHandle !== false) {
            $response['userHandle'] = $this->encoder->encode((string) $userHandle);
        }

        return [
            'id' => $credentialId,
            'type' => 'public-key',
            'response' => $response,
        ];
    }

    /**
     * Initialise an EC P-256 (ES256) key pair and its COSE key.
     *
     * @return void
     */
    private function initEc(): void
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if (!$key instanceof OpenSSLAsymmetricKey) {
            throw new \RuntimeException('Unable to generate EC test key.');
        }
        $this->privateKey = $key;

        $details = openssl_pkey_get_details($key);
        $x = $this->pad32((string) $details['ec']['x']);
        $y = $this->pad32((string) $details['ec']['y']);

        $this->coseKey = (string) CBOREncoder::encode([
            1 => 2,   // kty: EC2
            3 => -7,  // alg: ES256
            -1 => 1,  // crv: P-256
            -2 => new CBORByteString($x),
            -3 => new CBORByteString($y),
        ]);
    }

    /**
     * Initialise an RSA (RS256) key pair and its COSE key.
     *
     * @return void
     */
    private function initRsa(): void
    {
        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        if (!$key instanceof OpenSSLAsymmetricKey) {
            throw new \RuntimeException('Unable to generate RSA test key.');
        }
        $this->privateKey = $key;

        $details = openssl_pkey_get_details($key);
        $this->coseKey = (string) CBOREncoder::encode([
            1 => 3,     // kty: RSA
            3 => -257,  // alg: RS256
            -1 => new CBORByteString((string) $details['rsa']['n']),
            -2 => new CBORByteString((string) $details['rsa']['e']),
        ]);
    }

    /**
     * Build registration authenticator data with attested credential data.
     *
     * @param int $signCount
     * @param bool $uv
     * @param string $rpId
     * @param bool $omitAttestedData
     * @return string
     */
    private function registrationAuthData(int $signCount, bool $uv, string $rpId, bool $omitAttestedData): string
    {
        $flags = AuthenticatorData::FLAG_USER_PRESENT;
        if ($uv) {
            $flags |= AuthenticatorData::FLAG_USER_VERIFIED;
        }
        if (!$omitAttestedData) {
            $flags |= AuthenticatorData::FLAG_ATTESTED_CREDENTIAL_DATA;
        }

        $authData = hash('sha256', $rpId, true) . chr($flags) . pack('N', $signCount);
        if ($omitAttestedData) {
            return $authData;
        }

        return $authData
            . self::AAGUID
            . pack('n', strlen($this->credentialIdRaw))
            . $this->credentialIdRaw
            . $this->coseKey;
    }

    /**
     * Build assertion authenticator data (no attested credential data).
     *
     * @param int $signCount
     * @param bool $uv
     * @param string $rpId
     * @return string
     */
    private function assertionAuthData(int $signCount, bool $uv, string $rpId): string
    {
        $flags = AuthenticatorData::FLAG_USER_PRESENT;
        if ($uv) {
            $flags |= AuthenticatorData::FLAG_USER_VERIFIED;
        }

        return hash('sha256', $rpId, true) . chr($flags) . pack('N', $signCount);
    }

    /**
     * Build the raw clientDataJSON bytes.
     *
     * @param string $type
     * @param string $challenge
     * @param string $origin
     * @return string
     */
    private function clientDataJson(string $type, string $challenge, string $origin): string
    {
        return (string) json_encode(
            ['type' => $type, 'challenge' => $challenge, 'origin' => $origin],
            JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Left-pad a coordinate to 32 bytes (defensive; openssl usually returns 32).
     *
     * @param string $value
     * @return string
     */
    private function pad32(string $value): string
    {
        if (strlen($value) >= 32) {
            return substr($value, -32);
        }

        return str_pad($value, 32, "\x00", STR_PAD_LEFT);
    }
}
