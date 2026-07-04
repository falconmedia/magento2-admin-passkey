<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use CBOR\CBOREncoder;
use CBOR\Types\CBORByteString;
use FalconMedia\AdminPasskey\Model\WebAuthn\Asn1\DerEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;

/**
 * Converts a CBOR-encoded COSE_Key (RFC 8152) public key into a PEM
 * SubjectPublicKeyInfo that openssl can consume, and exposes the COSE algorithm.
 *
 * Supports the two algorithms offered during registration: ES256 (EC2 / P-256)
 * and RS256 (RSA). Reuses the native CBOR decoder (2tvenom/cborencode) per ADR
 * 0001; no new dependency is added.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class CoseKeyConverter
{
    /**
     * COSE_Key common and type-specific label identifiers (RFC 8152 §7.1, §13).
     */
    private const LABEL_KTY = 1;
    private const LABEL_ALG = 3;
    private const LABEL_EC_CRV = -1;
    private const LABEL_EC_X = -2;
    private const LABEL_EC_Y = -3;
    private const LABEL_RSA_N = -1;
    private const LABEL_RSA_E = -2;

    private const KTY_EC2 = 2;
    private const KTY_RSA = 3;
    private const CRV_P256 = 1;

    private const EC_COORDINATE_LENGTH = 32;

    /**
     * COSE algorithm identifiers offered by the registration option builder.
     */
    public const ALG_ES256 = -7;
    public const ALG_RS256 = -257;

    /**
     * DER-encoded AlgorithmIdentifier prefix for an id-ecPublicKey / secp256r1
     * SubjectPublicKeyInfo (RFC 5480). The uncompressed point follows.
     */
    private const EC_P256_SPKI_PREFIX =
        "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
        . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00";

    /**
     * DER-encoded AlgorithmIdentifier for rsaEncryption (OID 1.2.840.113549.1.1.1)
     * with a NULL parameter.
     */
    private const RSA_ALGORITHM_IDENTIFIER =
        "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

    public function __construct(
        private readonly DerEncoder $derEncoder
    ) {
    }

    /**
     * Resolve the COSE algorithm identifier declared by the key.
     *
     * @param string $coseKey Raw CBOR-encoded COSE_Key bytes.
     * @return int
     * @throws WebAuthnVerificationException
     */
    public function getAlgorithm(string $coseKey): int
    {
        $map = $this->decode($coseKey);
        if (!isset($map[self::LABEL_ALG]) || !is_int($map[self::LABEL_ALG])) {
            throw new WebAuthnVerificationException(__('The passkey public key is malformed.'));
        }

        return $map[self::LABEL_ALG];
    }

    /**
     * Convert the COSE_Key public key to a PEM SubjectPublicKeyInfo string.
     *
     * @param string $coseKey Raw CBOR-encoded COSE_Key bytes.
     * @return string
     * @throws WebAuthnVerificationException
     */
    public function toPem(string $coseKey): string
    {
        $map = $this->decode($coseKey);
        $kty = $map[self::LABEL_KTY] ?? null;
        $alg = $map[self::LABEL_ALG] ?? null;

        if ($kty === self::KTY_EC2 && $alg === self::ALG_ES256) {
            return $this->ec2ToPem($map);
        }
        if ($kty === self::KTY_RSA && $alg === self::ALG_RS256) {
            return $this->rsaToPem($map);
        }

        throw new WebAuthnVerificationException(__('Unsupported passkey public key algorithm.'));
    }

    /**
     * Decode raw COSE_Key bytes into a PHP map.
     *
     * @param string $coseKey
     * @return array<int|string, mixed>
     * @throws WebAuthnVerificationException
     */
    private function decode(string $coseKey): array
    {
        if ($coseKey === '') {
            throw new WebAuthnVerificationException(__('The passkey public key is missing.'));
        }

        $buffer = $coseKey;
        try {
            // CBOREncoder::decode() consumes $buffer by reference.
            $decoded = CBOREncoder::decode($buffer);
        } catch (\Throwable $e) {
            throw new WebAuthnVerificationException(__('The passkey public key could not be decoded.'), $e);
        }

        if (!is_array($decoded)) {
            throw new WebAuthnVerificationException(__('The passkey public key is malformed.'));
        }

        return $decoded;
    }

    /**
     * Build a PEM public key from an EC2 (P-256) COSE_Key map.
     *
     * @param array<int|string, mixed> $map
     * @return string
     * @throws WebAuthnVerificationException
     */
    private function ec2ToPem(array $map): string
    {
        if (($map[self::LABEL_EC_CRV] ?? null) !== self::CRV_P256) {
            throw new WebAuthnVerificationException(__('Unsupported passkey elliptic curve.'));
        }

        $x = $this->byteString($map[self::LABEL_EC_X] ?? null);
        $y = $this->byteString($map[self::LABEL_EC_Y] ?? null);
        if (strlen($x) !== self::EC_COORDINATE_LENGTH || strlen($y) !== self::EC_COORDINATE_LENGTH) {
            throw new WebAuthnVerificationException(__('The passkey public key is malformed.'));
        }

        $spki = self::EC_P256_SPKI_PREFIX . "\x04" . $x . $y;

        return $this->pem($spki);
    }

    /**
     * Build a PEM public key from an RSA COSE_Key map.
     *
     * @param array<int|string, mixed> $map
     * @return string
     * @throws WebAuthnVerificationException
     */
    private function rsaToPem(array $map): string
    {
        $modulus = $this->byteString($map[self::LABEL_RSA_N] ?? null);
        $exponent = $this->byteString($map[self::LABEL_RSA_E] ?? null);
        if ($modulus === '' || $exponent === '') {
            throw new WebAuthnVerificationException(__('The passkey public key is malformed.'));
        }

        $rsaPublicKey = $this->derEncoder->sequence(
            $this->derEncoder->integer($modulus) . $this->derEncoder->integer($exponent)
        );
        $spki = $this->derEncoder->sequence(
            $this->derEncoder->sequence(self::RSA_ALGORITHM_IDENTIFIER)
            . $this->derEncoder->bitString($rsaPublicKey)
        );

        return $this->pem($spki);
    }

    /**
     * Wrap DER SubjectPublicKeyInfo bytes in a PEM envelope.
     *
     * @param string $der
     * @return string
     */
    private function pem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\r\n"
            . chunk_split(base64_encode($der), 64)
            . "-----END PUBLIC KEY-----\r\n";
    }

    /**
     * Extract raw bytes from a decoded COSE value (CBORByteString or scalar).
     *
     * @param mixed $value
     * @return string
     */
    private function byteString(mixed $value): string
    {
        if ($value instanceof CBORByteString) {
            return (string) $value->get_byte_string();
        }

        return is_string($value) ? $value : '';
    }
}
