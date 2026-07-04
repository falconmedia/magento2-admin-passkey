<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn\Asn1;

/**
 * Minimal ASN.1 DER encoder for the few structures WebAuthn key/signature
 * handling needs: INTEGER, SEQUENCE, and BIT STRING. Kept dependency-free and
 * pure so it is fully unit-testable in isolation.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class DerEncoder
{
    /**
     * Encode a DER length prefix (short or long form).
     *
     * @param int $length
     * @return string
     */
    public function length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xFF) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    /**
     * Encode an unsigned big-endian magnitude as a DER INTEGER.
     *
     * Leading zero bytes are stripped and a single 0x00 padding byte is prepended
     * when the high bit is set, so the value is always interpreted as positive.
     *
     * @param string $magnitude Raw big-endian bytes.
     * @return string
     */
    public function integer(string $magnitude): string
    {
        $magnitude = ltrim($magnitude, "\x00");
        if ($magnitude === '') {
            $magnitude = "\x00";
        }
        if ((ord($magnitude[0]) & 0x80) !== 0) {
            $magnitude = "\x00" . $magnitude;
        }

        return "\x02" . $this->length(strlen($magnitude)) . $magnitude;
    }

    /**
     * Wrap the given content bytes in a DER SEQUENCE.
     *
     * @param string $content
     * @return string
     */
    public function sequence(string $content): string
    {
        return "\x30" . $this->length(strlen($content)) . $content;
    }

    /**
     * Wrap the given content bytes in a DER BIT STRING (zero unused bits).
     *
     * @param string $content
     * @return string
     */
    public function bitString(string $content): string
    {
        return "\x03" . $this->length(strlen($content) + 1) . "\x00" . $content;
    }
}
