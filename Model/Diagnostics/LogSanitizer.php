<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Diagnostics;

/**
 * Pure sanitiser that strips secrets from text before it is written into a
 * diagnostics bundle. Defence-in-depth: even sanitised sources are scrubbed for
 * secret-looking key/value pairs, bearer tokens and long opaque blobs.
 *
 * No I/O; fully unit testable.
 */
class LogSanitizer
{
    public const REDACTED = '[REDACTED]';

    /**
     * Case-insensitive key markers whose values must be redacted.
     *
     * @var string[]
     */
    private const SENSITIVE_KEYS = [
        'password',
        'passwd',
        'secret',
        'token',
        'api_key',
        'apikey',
        'private_key',
        'privatekey',
        'challenge',
        'credential_id',
        'assertion',
        'signature',
        'cookie',
        'authorization',
        'bearer',
        'session',
    ];

    /**
     * Redact secrets from a block of text.
     *
     * @param string $content
     * @return string
     */
    public function sanitize(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $keys = implode('|', array_map(static fn (string $key): string => preg_quote($key, '/'), self::SENSITIVE_KEYS));

        // key = value / key: value (quoted or bare), including JSON "key":"value".
        $patterns = [
            '/(["\']?(?:' . $keys . ')["\']?\s*[:=]\s*)(["\']?)([^\s"\',;}]+)(\2)/i',
            '/(Bearer\s+)([A-Za-z0-9\-._~+\/]+=*)/i',
        ];
        $replacements = [
            '$1$2' . self::REDACTED . '$4',
            '$1' . self::REDACTED,
        ];

        $result = (string) preg_replace($patterns, $replacements, $content);

        // Redact long opaque tokens (base64/hex/JWT-like) that could carry secrets.
        $result = (string) preg_replace('/\b[A-Za-z0-9+\/_-]{32,}={0,2}\b/', self::REDACTED, $result);

        return $result;
    }

    /**
     * Sanitise every string value in an array recursively.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sanitizeArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $result[$key] = self::REDACTED;
                continue;
            }
            if (is_array($value)) {
                $result[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $result[$key] = $this->sanitize($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Whether a key name looks sensitive.
     *
     * @param string $key
     * @return bool
     */
    private function isSensitiveKey(string $key): bool
    {
        $needle = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $marker) {
            if (str_contains($needle, $marker)) {
                return true;
            }
        }

        return false;
    }
}
