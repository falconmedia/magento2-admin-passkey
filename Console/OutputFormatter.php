<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Pure output-rendering helper shared by every AdminPasskey CLI command.
 *
 * Keeping format validation and JSON serialisation here (instead of in each
 * command) lets the commands stay thin and lets the formatting be unit tested
 * without booting Symfony's console runtime.
 *
 * @internal Console support; not part of a public web API contract.
 */
class OutputFormatter
{
    public const FORMAT_TABLE = 'table';
    public const FORMAT_JSON = 'json';

    /**
     * Supported `--format` values.
     *
     * @return string[]
     */
    public function getSupportedFormats(): array
    {
        return [self::FORMAT_TABLE, self::FORMAT_JSON];
    }

    /**
     * Whether the given output format is supported.
     *
     * @param string $format
     * @return bool
     */
    public function isValidFormat(string $format): bool
    {
        return in_array($format, $this->getSupportedFormats(), true);
    }

    /**
     * Validate an output format, throwing on an unsupported value.
     *
     * @param string $format
     * @return void
     * @throws \InvalidArgumentException
     */
    public function assertValidFormat(string $format): void
    {
        if (!$this->isValidFormat($format)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported format "%s". Use one of: %s.',
                    $format,
                    implode(', ', $this->getSupportedFormats())
                )
            );
        }
    }

    /**
     * Whether the requested format is JSON.
     *
     * @param string $format
     * @return bool
     */
    public function isJson(string $format): bool
    {
        return $format === self::FORMAT_JSON;
    }

    /**
     * Serialise a payload to human-readable JSON.
     *
     * @param array<int|string, mixed> $data
     * @return string
     */
    public function toJson(array $data): string
    {
        return (string) json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Render a table to the given output stream.
     *
     * @param OutputInterface $output
     * @param string[] $headers
     * @param array<int, array<int, string>> $rows
     * @return void
     */
    public function renderTable(OutputInterface $output, array $headers, array $rows): void
    {
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }
}
