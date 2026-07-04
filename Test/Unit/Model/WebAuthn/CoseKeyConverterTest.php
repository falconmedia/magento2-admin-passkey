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
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for COSE_Key -> PEM conversion for both ES256 and RS256.
 */
class CoseKeyConverterTest extends TestCase
{
    private CoseKeyConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new CoseKeyConverter(new DerEncoder());
    }

    public function testEs256KeyConvertsToUsablePem(): void
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

        $this->assertSame(CoseKeyConverter::ALG_ES256, $this->converter->getAlgorithm($cose));

        $pem = $this->converter->toPem($cose);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $pem);
        $this->assertNotFalse(openssl_pkey_get_public($pem), 'openssl must accept the derived EC PEM');
        $this->assertSame($details['ec']['x'], openssl_pkey_get_details(openssl_pkey_get_public($pem))['ec']['x']);
    }

    public function testRs256KeyConvertsToUsablePem(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $details = openssl_pkey_get_details($key);
        $cose = (string) CBOREncoder::encode([
            1 => 3,
            3 => -257,
            -1 => new CBORByteString((string) $details['rsa']['n']),
            -2 => new CBORByteString((string) $details['rsa']['e']),
        ]);

        $this->assertSame(CoseKeyConverter::ALG_RS256, $this->converter->getAlgorithm($cose));

        $pem = $this->converter->toPem($cose);
        $public = openssl_pkey_get_public($pem);
        $this->assertNotFalse($public, 'openssl must accept the derived RSA PEM');
        $this->assertSame($details['rsa']['n'], openssl_pkey_get_details($public)['rsa']['n']);
    }

    public function testUnsupportedAlgorithmRejected(): void
    {
        $cose = (string) CBOREncoder::encode([
            1 => 4,
            3 => -8, // EdDSA, not supported.
            -1 => 6,
            -2 => new CBORByteString(str_repeat("\x01", 32)),
        ]);

        $this->expectException(WebAuthnVerificationException::class);
        $this->converter->toPem($cose);
    }

    public function testMalformedKeyRejected(): void
    {
        $this->expectException(WebAuthnVerificationException::class);
        $this->converter->getAlgorithm('not-cbor');
    }
}
