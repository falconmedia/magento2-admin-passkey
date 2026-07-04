<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Test\Unit\Model\WebAuthn;

use FalconMedia\AdminPasskey\Model\WebAuthn\ClientDataParser;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for clientDataJSON parsing and matching.
 */
class ClientDataParserTest extends TestCase
{
    private ClientDataParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ClientDataParser(new Json());
    }

    public function testParseValidClientData(): void
    {
        $json = (string) json_encode([
            'type' => 'webauthn.get',
            'challenge' => 'CHALLENGE',
            'origin' => 'https://example.com',
            'crossOrigin' => false,
        ]);

        $parsed = $this->parser->parse($json);

        $this->assertSame('webauthn.get', $parsed['type']);
        $this->assertSame('CHALLENGE', $parsed['challenge']);
        $this->assertSame('https://example.com', $parsed['origin']);
    }

    public function testParseRejectsInvalidJson(): void
    {
        $this->expectException(WebAuthnVerificationException::class);
        $this->parser->parse('{not valid json');
    }

    public function testParseRejectsMissingFields(): void
    {
        $this->expectException(WebAuthnVerificationException::class);
        $this->parser->parse((string) json_encode(['type' => 'webauthn.get']));
    }

    public function testAssertMatchesSucceeds(): void
    {
        $clientData = ['type' => 'webauthn.get', 'challenge' => 'C', 'origin' => 'https://example.com'];
        $this->parser->assertMatches($clientData, ClientDataParser::TYPE_GET, 'C', 'https://example.com');

        $this->addToAssertionCount(1);
    }

    public function testAssertMatchesRejectsWrongType(): void
    {
        $clientData = ['type' => 'webauthn.create', 'challenge' => 'C', 'origin' => 'https://example.com'];

        $this->expectException(WebAuthnVerificationException::class);
        $this->parser->assertMatches($clientData, ClientDataParser::TYPE_GET, 'C', 'https://example.com');
    }

    public function testAssertMatchesRejectsWrongChallenge(): void
    {
        $clientData = ['type' => 'webauthn.get', 'challenge' => 'OTHER', 'origin' => 'https://example.com'];

        $this->expectException(WebAuthnVerificationException::class);
        $this->parser->assertMatches($clientData, ClientDataParser::TYPE_GET, 'C', 'https://example.com');
    }

    public function testAssertMatchesRejectsWrongOrigin(): void
    {
        $clientData = ['type' => 'webauthn.get', 'challenge' => 'C', 'origin' => 'https://evil.example'];

        $this->expectException(WebAuthnVerificationException::class);
        $this->parser->assertMatches($clientData, ClientDataParser::TYPE_GET, 'C', 'https://example.com');
    }
}
