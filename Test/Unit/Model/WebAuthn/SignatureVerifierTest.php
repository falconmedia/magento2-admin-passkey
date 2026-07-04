<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use CBOR\CBOREncoder;
use CBOR\Types\CBORByteString;
use FalconMedia\AdminPasskey\Model\WebAuthn\Asn1\DerEncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\CoseKeyConverter;
use FalconMedia\AdminPasskey\Model\WebAuthn\SignatureVerifier;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for real ES256/RS256 signature verification, including the raw
 * r||s -> DER conversion path.
 */
class SignatureVerifierTest extends TestCase
{
    private SignatureVerifier $verifier;

    protected function setUp(): void
    {
        $derEncoder = new DerEncoder();
        $this->verifier = new SignatureVerifier(new CoseKeyConverter($derEncoder), $derEncoder);
    }

    public function testValidEs256DerSignatureVerifies(): void
    {
        [$cose, $key] = $this->ecKey();
        $data = random_bytes(96);
        $signature = '';
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);

        $this->assertTrue($this->verifier->verify($cose, $data, $signature));
    }

    public function testTamperedDataFailsEs256(): void
    {
        [$cose, $key] = $this->ecKey();
        $data = random_bytes(96);
        $signature = '';
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);

        $this->assertFalse($this->verifier->verify($cose, $data . "\x00", $signature));
    }

    public function testWrongKeyFailsEs256(): void
    {
        [$cose] = $this->ecKey();
        [, $otherKey] = $this->ecKey();
        $data = random_bytes(96);
        $signature = '';
        openssl_sign($data, $signature, $otherKey, OPENSSL_ALGO_SHA256);

        $this->assertFalse($this->verifier->verify($cose, $data, $signature));
    }

    public function testRawRsSignatureIsConvertedAndVerifies(): void
    {
        [$cose, $key] = $this->ecKey();
        $data = random_bytes(96);
        $der = '';
        openssl_sign($data, $der, $key, OPENSSL_ALGO_SHA256);

        $raw = $this->derEcdsaToRaw($der);
        $this->assertSame(64, strlen($raw));
        $this->assertTrue($this->verifier->verify($cose, $data, $raw));
    }

    public function testConvertRawEcdsaSignatureToDerRoundTrips(): void
    {
        [$cose, $key] = $this->ecKey();
        $data = random_bytes(96);
        $der = '';
        openssl_sign($data, $der, $key, OPENSSL_ALGO_SHA256);

        $raw = $this->derEcdsaToRaw($der);
        $rebuilt = $this->verifier->convertRawEcdsaSignatureToDer($raw);

        // Rebuilt DER must verify against the same key/data.
        $pem = (new CoseKeyConverter(new DerEncoder()))->toPem($cose);
        $this->assertSame(1, openssl_verify($data, $rebuilt, $pem, OPENSSL_ALGO_SHA256));
    }

    public function testValidRs256SignatureVerifies(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $details = openssl_pkey_get_details($key);
        $cose = (string) CBOREncoder::encode([
            1 => 3,
            3 => -257,
            -1 => new CBORByteString((string) $details['rsa']['n']),
            -2 => new CBORByteString((string) $details['rsa']['e']),
        ]);

        $data = random_bytes(96);
        $signature = '';
        openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);

        $this->assertTrue($this->verifier->verify($cose, $data, $signature));
        $this->assertFalse($this->verifier->verify($cose, $data . "\x00", $signature));
    }

    /**
     * Create an EC P-256 key pair and its COSE encoding.
     *
     * @return array{0: string, 1: \OpenSSLAsymmetricKey}
     */
    private function ecKey(): array
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $details = openssl_pkey_get_details($key);
        $cose = (string) CBOREncoder::encode([
            1 => 2,
            3 => -7,
            -1 => 1,
            -2 => new CBORByteString(str_pad((string) $details['ec']['x'], 32, "\x00", STR_PAD_LEFT)),
            -3 => new CBORByteString(str_pad((string) $details['ec']['y'], 32, "\x00", STR_PAD_LEFT)),
        ]);

        return [$cose, $key];
    }

    /**
     * Convert a DER ECDSA signature into a raw 64-byte r||s form for testing.
     *
     * @param string $der
     * @return string
     */
    private function derEcdsaToRaw(string $der): string
    {
        $offset = 2; // SEQUENCE header + length (short form for P-256).
        $offset++; // INTEGER tag for r.
        $rLen = ord($der[$offset]);
        $offset++;
        $r = substr($der, $offset, $rLen);
        $offset += $rLen;
        $offset++; // INTEGER tag for s.
        $sLen = ord($der[$offset]);
        $offset++;
        $s = substr($der, $offset, $sLen);

        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }
}
