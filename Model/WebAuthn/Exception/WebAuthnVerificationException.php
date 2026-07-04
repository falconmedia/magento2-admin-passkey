<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\WebAuthn\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Raised when a WebAuthn registration or assertion ceremony fails any security
 * validation (invalid/expired/consumed challenge, origin/rpId mismatch, bad
 * signature, sign-counter regression, revoked/unknown credential, and so on).
 *
 * Callers (Step 11 controllers) should map this to a single generic user-facing
 * error and must never treat a thrown verification as a successful login.
 *
 * @internal Admin-only WebAuthn support; not part of a public web API contract.
 */
class WebAuthnVerificationException extends LocalizedException
{
    /**
     * Accept any \Throwable as the cause so call sites can catch \Throwable
     * (per coding standards) and still chain the original error. \Error causes
     * cannot be forwarded to the parent (which only accepts \Exception) and are
     * intentionally dropped from the chain.
     *
     * @param Phrase $phrase
     * @param \Throwable|null $cause
     * @param int $code
     */
    public function __construct(Phrase $phrase, ?\Throwable $cause = null, int $code = 0)
    {
        parent::__construct($phrase, $cause instanceof \Exception ? $cause : null, $code);
    }
}
