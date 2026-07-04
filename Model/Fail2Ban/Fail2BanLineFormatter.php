<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Fail2Ban;

/**
 * Formats a single, Fail2Ban-parseable log line.
 *
 * The line is deterministic and single-line: a leading timestamp, a fixed tag, an
 * event code, and space-separated key=value fields (with the IP always present so
 * a Fail2Ban failregex can capture it). Values are sanitised so a hostile
 * user-agent or username cannot inject a newline or break the field grammar.
 */
class Fail2BanLineFormatter
{
    /**
     * Fixed tag identifying the source of the log line.
     */
    public const TAG = 'FalconMedia_AdminPasskey';

    /**
     * Placeholder used when a field value is unknown.
     */
    private const PLACEHOLDER = '-';

    /**
     * Build a single Fail2Ban log line (no trailing newline).
     *
     * @param string $timestamp Line timestamp (Y-m-d H:i:s, UTC).
     * @param string $eventType Event code (e.g. login_failed, lockout, brute_force_detected).
     * @param string|null $ip Remote IP address, when known.
     * @param string|null $username Attempted username, when known.
     * @param int|null $adminUserId Admin user id, when known.
     * @param string|null $method Login method (passkey|password), when known.
     * @return string
     */
    public function format(
        string $timestamp,
        string $eventType,
        ?string $ip,
        ?string $username,
        ?int $adminUserId,
        ?string $method
    ): string {
        return sprintf(
            '%s %s event=%s ip=%s user="%s" admin_id=%s method=%s',
            $timestamp,
            self::TAG,
            $this->token($eventType),
            $this->token($ip ?? self::PLACEHOLDER),
            $this->quoted($username),
            $adminUserId !== null && $adminUserId > 0 ? (string) $adminUserId : self::PLACEHOLDER,
            $this->token($method ?? self::PLACEHOLDER)
        );
    }

    /**
     * Sanitise an unquoted token: strip whitespace/control characters.
     *
     * @param string $value
     * @return string
     */
    private function token(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9._:\-]/', '', $value);

        return ($clean === null || $clean === '') ? self::PLACEHOLDER : $clean;
    }

    /**
     * Sanitise a quoted value: strip control characters and double quotes.
     *
     * @param string|null $value
     * @return string
     */
    private function quoted(?string $value): string
    {
        if ($value === null || $value === '') {
            return self::PLACEHOLDER;
        }

        $clean = preg_replace('/[\x00-\x1F\x7F"\\\\]/', '', $value);

        return $clean === null || $clean === '' ? self::PLACEHOLDER : $clean;
    }
}
