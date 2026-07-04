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
use FalconMedia\AdminPasskey\Model\WebAuthn\AuthenticatorDataParser;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for authenticator data parsing, including attested credential data.
 */
class AuthenticatorDataParserTest extends TestCase
{
    private AuthenticatorDataParser $parser;

    protected function setUp(): void
    {
        $this->parser = new AuthenticatorDataParser();
    }

    public function testParseAssertionAuthData(): void
    {
        $rpIdHash = hash('sha256', 'example.com', true);
        $flags = AuthenticatorData::FLAG_USER_PRESENT | AuthenticatorData::FLAG_USER_VERIFIED;
        $authData = $rpIdHash . chr($flags) . pack('N', 123);

        $parsed = $this->parser->parse($authData);

        $this->assertSame($rpIdHash, $parsed->getRpIdHash());
        $this->assertTrue($parsed->isUserPresent());
        $this->assertTrue($parsed->isUserVerified());
        $this->assertFalse($parsed->hasAttestedCredentialData());
        $this->assertSame(123, $parsed->getSignCount());
        $this->assertNull($parsed->getCoseKey());
    }

    public function testParseRegistrationAuthDataWithAttestedCredentialData(): void
    {
        $rpIdHash = hash('sha256', 'example.com', true);
        $flags = AuthenticatorData::FLAG_USER_PRESENT | AuthenticatorData::FLAG_ATTESTED_CREDENTIAL_DATA;
        $aaguid = str_repeat("\x11", 16);
        $credentialId = random_bytes(20);
        $cose = (string) CBOREncoder::encode([
            1 => 2,
            3 => -7,
            -1 => 1,
            -2 => new CBORByteString(str_repeat("\x02", 32)),
            -3 => new CBORByteString(str_repeat("\x03", 32)),
        ]);

        // Append trailing extension bytes to ensure the COSE key length is computed exactly.
        $authData = $rpIdHash . chr($flags) . pack('N', 0)
            . $aaguid . pack('n', strlen($credentialId)) . $credentialId . $cose . "\xAA\xBB";

        $parsed = $this->parser->parse($authData);

        $this->assertTrue($parsed->hasAttestedCredentialData());
        $this->assertSame($aaguid, $parsed->getAaguid());
        $this->assertSame($credentialId, $parsed->getCredentialId());
        $this->assertSame($cose, $parsed->getCoseKey(), 'COSE key must be extracted exactly, ignoring trailing bytes');
    }

    public function testTooShortAuthDataRejected(): void
    {
        $this->expectException(WebAuthnVerificationException::class);
        $this->parser->parse('short');
    }

    public function testTruncatedAttestedDataRejected(): void
    {
        $rpIdHash = hash('sha256', 'example.com', true);
        $flags = AuthenticatorData::FLAG_USER_PRESENT | AuthenticatorData::FLAG_ATTESTED_CREDENTIAL_DATA;
        // AT flag set but no attested credential data follows.
        $authData = $rpIdHash . chr($flags) . pack('N', 0);

        $this->expectException(WebAuthnVerificationException::class);
        $this->parser->parse($authData);
    }
}
