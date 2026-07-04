<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn;

/**
 * Immutable parsed representation of WebAuthn authenticator data
 * (@see https://www.w3.org/TR/webauthn-2/#sctn-authenticator-data).
 *
 * Attested credential fields (aaguid, credentialId, coseKey) are only populated
 * when the AT flag is set, i.e. for registration (attestation) responses.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class AuthenticatorData
{
    /**
     * Authenticator data flag bits.
     */
    public const FLAG_USER_PRESENT = 0x01;
    public const FLAG_USER_VERIFIED = 0x04;
    public const FLAG_ATTESTED_CREDENTIAL_DATA = 0x40;
    public const FLAG_EXTENSION_DATA = 0x80;

    public function __construct(
        private readonly string $rpIdHash,
        private readonly int $flags,
        private readonly int $signCount,
        private readonly ?string $aaguid = null,
        private readonly ?string $credentialId = null,
        private readonly ?string $coseKey = null
    ) {
    }

    /**
     * Raw 32-byte SHA-256 hash of the relying party id.
     *
     * @return string
     */
    public function getRpIdHash(): string
    {
        return $this->rpIdHash;
    }

    /**
     * Raw flags byte value.
     *
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * Signature counter reported by the authenticator.
     *
     * @return int
     */
    public function getSignCount(): int
    {
        return $this->signCount;
    }

    /**
     * Raw 16-byte AAGUID, or null when no attested credential data is present.
     *
     * @return string|null
     */
    public function getAaguid(): ?string
    {
        return $this->aaguid;
    }

    /**
     * Raw credential id bytes, or null when no attested credential data is present.
     *
     * @return string|null
     */
    public function getCredentialId(): ?string
    {
        return $this->credentialId;
    }

    /**
     * Raw CBOR COSE_Key bytes, or null when no attested credential data is present.
     *
     * @return string|null
     */
    public function getCoseKey(): ?string
    {
        return $this->coseKey;
    }

    /**
     * Whether the user-present flag is set.
     *
     * @return bool
     */
    public function isUserPresent(): bool
    {
        return ($this->flags & self::FLAG_USER_PRESENT) !== 0;
    }

    /**
     * Whether the user-verified flag is set.
     *
     * @return bool
     */
    public function isUserVerified(): bool
    {
        return ($this->flags & self::FLAG_USER_VERIFIED) !== 0;
    }

    /**
     * Whether attested credential data is present (AT flag).
     *
     * @return bool
     */
    public function hasAttestedCredentialData(): bool
    {
        return ($this->flags & self::FLAG_ATTESTED_CREDENTIAL_DATA) !== 0;
    }
}
