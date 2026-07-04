<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

use CBOR\CBOREncoder;
use FalconMedia\AdminPasskey\Model\WebAuthn\Exception\WebAuthnVerificationException;

/**
 * Parses raw WebAuthn authenticator data bytes into an {@see AuthenticatorData}
 * value object, including attested credential data (aaguid, credential id, COSE
 * public key) when the AT flag is present.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class AuthenticatorDataParser
{
    private const RP_ID_HASH_LENGTH = 32;
    private const FLAGS_OFFSET = 32;
    private const SIGN_COUNT_OFFSET = 33;
    private const SIGN_COUNT_LENGTH = 4;
    private const ATTESTED_DATA_OFFSET = 37;
    private const AAGUID_LENGTH = 16;
    private const CREDENTIAL_ID_LENGTH_BYTES = 2;

    /**
     * Minimum length of authenticator data without attested credential data.
     */
    private const MIN_LENGTH = self::SIGN_COUNT_OFFSET + self::SIGN_COUNT_LENGTH;

    /**
     * Parse raw authenticator data.
     *
     * @param string $authenticatorData Raw authenticator data bytes.
     * @return AuthenticatorData
     * @throws WebAuthnVerificationException
     */
    public function parse(string $authenticatorData): AuthenticatorData
    {
        if (strlen($authenticatorData) < self::MIN_LENGTH) {
            throw new WebAuthnVerificationException(__('The passkey authenticator data is malformed.'));
        }

        $rpIdHash = substr($authenticatorData, 0, self::RP_ID_HASH_LENGTH);
        $flags = ord($authenticatorData[self::FLAGS_OFFSET]);
        $signCount = $this->readUint32($authenticatorData, self::SIGN_COUNT_OFFSET);

        if (($flags & AuthenticatorData::FLAG_ATTESTED_CREDENTIAL_DATA) === 0) {
            return new AuthenticatorData($rpIdHash, $flags, $signCount);
        }

        return $this->parseWithAttestedCredentialData($authenticatorData, $rpIdHash, $flags, $signCount);
    }

    /**
     * Parse authenticator data that includes attested credential data.
     *
     * @param string $authenticatorData
     * @param string $rpIdHash
     * @param int $flags
     * @param int $signCount
     * @return AuthenticatorData
     * @throws WebAuthnVerificationException
     */
    private function parseWithAttestedCredentialData(
        string $authenticatorData,
        string $rpIdHash,
        int $flags,
        int $signCount
    ): AuthenticatorData {
        $credentialIdLengthOffset = self::ATTESTED_DATA_OFFSET + self::AAGUID_LENGTH;
        if (strlen($authenticatorData) < $credentialIdLengthOffset + self::CREDENTIAL_ID_LENGTH_BYTES) {
            throw new WebAuthnVerificationException(__('The passkey attestation is missing required data.'));
        }

        $aaguid = substr($authenticatorData, self::ATTESTED_DATA_OFFSET, self::AAGUID_LENGTH);
        $credentialIdLength = $this->readUint16($authenticatorData, $credentialIdLengthOffset);

        $credentialIdOffset = $credentialIdLengthOffset + self::CREDENTIAL_ID_LENGTH_BYTES;
        if (strlen($authenticatorData) < $credentialIdOffset + $credentialIdLength) {
            throw new WebAuthnVerificationException(__('The passkey attestation is missing required data.'));
        }

        $credentialId = substr($authenticatorData, $credentialIdOffset, $credentialIdLength);
        $coseKey = $this->extractCoseKey(substr($authenticatorData, $credentialIdOffset + $credentialIdLength));

        return new AuthenticatorData($rpIdHash, $flags, $signCount, $aaguid, $credentialId, $coseKey);
    }

    /**
     * Extract exactly the CBOR-encoded COSE_Key bytes from the trailing data,
     * ignoring any following extension bytes.
     *
     * @param string $remaining
     * @return string
     * @throws WebAuthnVerificationException
     */
    private function extractCoseKey(string $remaining): string
    {
        if ($remaining === '') {
            throw new WebAuthnVerificationException(__('The passkey public key is missing.'));
        }

        $buffer = $remaining;
        try {
            // CBOREncoder::decode() consumes $buffer by reference, leaving only trailing bytes.
            $decoded = CBOREncoder::decode($buffer);
        } catch (\Throwable $e) {
            throw new WebAuthnVerificationException(__('The passkey public key could not be decoded.'), $e);
        }

        if (!is_array($decoded)) {
            throw new WebAuthnVerificationException(__('The passkey public key is malformed.'));
        }

        $consumedLength = strlen($remaining) - strlen($buffer);
        if ($consumedLength <= 0) {
            throw new WebAuthnVerificationException(__('The passkey public key is malformed.'));
        }

        return substr($remaining, 0, $consumedLength);
    }

    /**
     * Read a big-endian unsigned 32-bit integer at the given offset.
     *
     * @param string $bytes
     * @param int $offset
     * @return int
     */
    private function readUint32(string $bytes, int $offset): int
    {
        $unpacked = unpack('N', substr($bytes, $offset, self::SIGN_COUNT_LENGTH));

        return $unpacked !== false ? (int) $unpacked[1] : 0;
    }

    /**
     * Read a big-endian unsigned 16-bit integer at the given offset.
     *
     * @param string $bytes
     * @param int $offset
     * @return int
     */
    private function readUint16(string $bytes, int $offset): int
    {
        $unpacked = unpack('n', substr($bytes, $offset, self::CREDENTIAL_ID_LENGTH_BYTES));

        return $unpacked !== false ? (int) $unpacked[1] : 0;
    }
}
