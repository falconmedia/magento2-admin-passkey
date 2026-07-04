<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use FalconMedia\AdminPasskey\Model\WebAuthn\Asn1\DerEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;

/**
 * Verifies a WebAuthn assertion signature against a stored COSE public key.
 *
 * The signed data is always {@code authenticatorData || SHA-256(clientDataJSON)}.
 * ES256 (ECDSA/P-256) and RS256 (RSASSA-PKCS1-v1_5) are supported via openssl.
 * For ES256 a raw {@code r || s} (64-byte) signature is transparently converted
 * to the ASN.1 DER form openssl expects; genuine authenticators already emit DER.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class SignatureVerifier
{
    /**
     * Length in bytes of a raw ES256 (P-256) {@code r || s} signature.
     */
    private const RAW_ES256_SIGNATURE_LENGTH = 64;

    private const EC_COORDINATE_LENGTH = 32;

    public function __construct(
        private readonly CoseKeyConverter $coseKeyConverter,
        private readonly DerEncoder $derEncoder
    ) {
    }

    /**
     * Verify a signature over the signed data using the stored COSE public key.
     *
     * @param string $coseKey Raw CBOR-encoded COSE_Key public key bytes.
     * @param string $signedData authenticatorData concatenated with SHA-256(clientDataJSON).
     * @param string $signature Raw signature bytes as sent by the authenticator.
     * @return bool True only when the signature is cryptographically valid.
     * @throws WebAuthnVerificationException When the key is unusable.
     */
    public function verify(string $coseKey, string $signedData, string $signature): bool
    {
        if ($signature === '') {
            return false;
        }

        $algorithm = $this->coseKeyConverter->getAlgorithm($coseKey);
        $pem = $this->coseKeyConverter->toPem($coseKey);

        $verifiable = match ($algorithm) {
            CoseKeyConverter::ALG_ES256 => $this->normaliseEs256Signature($signature),
            CoseKeyConverter::ALG_RS256 => $signature,
            default => throw new WebAuthnVerificationException(
                __('Unsupported passkey public key algorithm.')
            ),
        };

        return openssl_verify($signedData, $verifiable, $pem, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Normalise an ES256 signature to DER, converting raw {@code r || s} if needed.
     *
     * @param string $signature
     * @return string
     * @throws WebAuthnVerificationException
     */
    private function normaliseEs256Signature(string $signature): string
    {
        if (strlen($signature) === self::RAW_ES256_SIGNATURE_LENGTH) {
            return $this->convertRawEcdsaSignatureToDer($signature);
        }

        return $signature;
    }

    /**
     * Convert a raw {@code r || s} ES256 signature (64 bytes) into ASN.1 DER.
     *
     * Exposed for direct unit testing of the conversion against known vectors.
     *
     * @param string $signature 64-byte raw signature.
     * @return string DER-encoded ECDSA signature.
     * @throws WebAuthnVerificationException
     */
    public function convertRawEcdsaSignatureToDer(string $signature): string
    {
        if (strlen($signature) !== self::RAW_ES256_SIGNATURE_LENGTH) {
            throw new WebAuthnVerificationException(__('The passkey signature is malformed.'));
        }

        $r = substr($signature, 0, self::EC_COORDINATE_LENGTH);
        $s = substr($signature, self::EC_COORDINATE_LENGTH, self::EC_COORDINATE_LENGTH);

        return $this->derEncoder->sequence(
            $this->derEncoder->integer($r) . $this->derEncoder->integer($s)
        );
    }
}
