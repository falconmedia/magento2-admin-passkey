<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\TrustedDevice;

use Magento\Framework\Math\Random;

/**
 * Generates trusted-device tokens and derives their storage hash.
 *
 * The raw token is only ever handed to the browser (as a cookie); the database
 * stores nothing but the SHA-256 hash, so a database leak cannot reveal a usable
 * device token. Lookups hash the incoming cookie value and compare hashes.
 */
class TrustedDeviceTokenizer
{
    /**
     * Raw token length in characters (before hashing).
     */
    private const TOKEN_LENGTH = 64;

    public function __construct(
        private readonly Random $random
    ) {
    }

    /**
     * Generate a new, cryptographically random raw device token.
     *
     * @return string
     */
    public function generateToken(): string
    {
        return $this->random->getRandomString(self::TOKEN_LENGTH);
    }

    /**
     * Derive the storage hash for a raw device token.
     *
     * @param string $token
     * @return string 64-char lowercase hex SHA-256 digest.
     */
    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
