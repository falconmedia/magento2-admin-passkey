<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Model\Diagnostics;

/**
 * Pure builder for the diagnostics manifest structure.
 *
 * The manifest is a description of the bundle contents; it never contains raw
 * secrets. No I/O; fully unit testable.
 */
class ManifestBuilder
{
    public const MANIFEST_VERSION = '1.0';

    /**
     * Build the manifest array.
     *
     * Recognised (all optional) keys: support_reference_id, generated_at, versions,
     * counts, health, score, files, config.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function build(array $context): array
    {
        $health = $this->asArray($context['health'] ?? []);
        $files = $this->asArray($context['files'] ?? []);

        return [
            'manifest_version' => self::MANIFEST_VERSION,
            'support_reference_id' => $context['support_reference_id'] ?? '',
            'generated_at' => $context['generated_at'] ?? '',
            'versions' => $this->asArray($context['versions'] ?? []),
            'counts' => $this->asArray($context['counts'] ?? []),
            'health' => [
                'overall' => $this->resolveOverallHealth($health),
                'checks' => $health,
            ],
            'security_score' => $this->asArray($context['score'] ?? []),
            'config' => $this->asArray($context['config'] ?? []),
            'files' => array_values($files),
            'notice' => 'This bundle contains no passwords, keys, tokens or other secrets.',
        ];
    }

    /**
     * Derive the overall health status from the individual checks.
     *
     * @param array<int|string, mixed> $checks
     * @return string
     */
    private function resolveOverallHealth(array $checks): string
    {
        $hasWarning = false;
        foreach ($checks as $check) {
            $status = is_array($check) ? ($check['status'] ?? '') : '';
            if ($status === 'error') {
                return 'error';
            }
            if ($status === 'warning') {
                $hasWarning = true;
            }
        }

        return $hasWarning ? 'warning' : 'ok';
    }

    /**
     * Coerce a mixed value to an array.
     *
     * @param mixed $value
     * @return array<int|string, mixed>
     */
    private function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
