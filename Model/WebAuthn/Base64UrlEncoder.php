<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

/**
 * RFC 4648 §5 base64url helper (unpadded), as used by WebAuthn for challenge,
 * user handle, and credential id transport values.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class Base64UrlEncoder
{
    /**
     * Encode raw bytes as unpadded base64url.
     *
     * @param string $binary
     * @return string
     */
    public function encode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    /**
     * Decode an unpadded (or padded) base64url string back to raw bytes.
     *
     * @param string $encoded
     * @return string
     */
    public function decode(string $encoded): string
    {
        $padded = strtr($encoded, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder > 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode($padded, true);
    }
}
