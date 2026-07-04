<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Api\AuditLogInterface;
use FalconMedia\AdminPasskey\Api\Data\AuditEventInterface;
use FalconMedia\AdminPasskey\Console\AuditExportFilter;
use FalconMedia\AdminPasskey\Console\AuditFilterParser;
use FalconMedia\AdminPasskey\Console\OutputFormatter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Filesystem\Driver\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Exports audit events to CSV or JSON, to a file or stdout.
 *
 * Delegates reads to {@see AuditLogInterface} and filter parsing to
 * {@see AuditFilterParser}. Read-only with respect to module data.
 */
class AuditExportCommand extends Command
{
    private const FORMAT_CSV = 'csv';
    private const FORMAT_JSON = 'json';

    private const OPTION_FORMAT = 'format';
    private const OPTION_OUTPUT = 'output';
    private const OPTION_FROM = 'from';
    private const OPTION_TO = 'to';
    private const OPTION_TYPE = 'type';

    /**
     * Exported columns, in order.
     *
     * @var string[]
     */
    private const COLUMNS = [
        AuditEventInterface::ENTITY_ID,
        AuditEventInterface::EVENT_TYPE,
        AuditEventInterface::SEVERITY,
        AuditEventInterface::ACTOR_ADMIN_USER_ID,
        AuditEventInterface::TARGET_ADMIN_USER_ID,
        AuditEventInterface::IP,
        AuditEventInterface::USER_AGENT,
        AuditEventInterface::SUPPORT_REFERENCE_ID,
        AuditEventInterface::METADATA,
        AuditEventInterface::CREATED_AT,
    ];

    public function __construct(
        private readonly AuditLogInterface $auditLog,
        private readonly AuditFilterParser $filterParser,
        private readonly OutputFormatter $formatter,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly File $file,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('adminpasskey:audit:export');
        $this->setDescription('Export audit events to CSV or JSON, to a file or stdout.');
        $this->addOption(
            self::OPTION_FORMAT,
            null,
            InputOption::VALUE_REQUIRED,
            'Export format: csv or json.',
            self::FORMAT_CSV
        );
        $this->addOption(
            self::OPTION_OUTPUT,
            'o',
            InputOption::VALUE_REQUIRED,
            'Write to this file path instead of stdout.'
        );
        $this->addOption(
            self::OPTION_FROM,
            null,
            InputOption::VALUE_REQUIRED,
            'Inclusive lower bound, "Y-m-d" or "Y-m-d H:i:s" (UTC).'
        );
        $this->addOption(
            self::OPTION_TO,
            null,
            InputOption::VALUE_REQUIRED,
            'Inclusive upper bound, "Y-m-d" or "Y-m-d H:i:s" (UTC).'
        );
        $this->addOption(
            self::OPTION_TYPE,
            null,
            InputOption::VALUE_REQUIRED,
            'Filter by event-type code (e.g. passkey_login).'
        );
        $this->setHelp(
            'Exports audit events matching the optional --from/--to/--type filters as CSV (default) or'
            . ' JSON. Without --output the export is written to stdout. Returns exit code 2 on an invalid'
            . ' argument and 1 when the export cannot be written.'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption(self::OPTION_FORMAT));
        if (!in_array($format, [self::FORMAT_CSV, self::FORMAT_JSON], true)) {
            $output->writeln(sprintf('<error>Unsupported format "%s". Use csv or json.</error>', $format));

            return Command::INVALID;
        }

        try {
            $filter = $this->filterParser->parse(
                $this->optionAsString($input->getOption(self::OPTION_FROM)),
                $this->optionAsString($input->getOption(self::OPTION_TO)),
                $this->optionAsString($input->getOption(self::OPTION_TYPE))
            );
        } catch (\InvalidArgumentException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::INVALID;
        }

        $rows = $this->collectRows($filter);
        $content = $format === self::FORMAT_JSON ? $this->formatter->toJson($rows) : $this->toCsv($rows);

        $path = $this->optionAsString($input->getOption(self::OPTION_OUTPUT));
        if ($path === null) {
            $output->writeln($content);

            return Command::SUCCESS;
        }

        try {
            $this->file->filePutContents($path, $content);
        } catch (\Throwable $exception) {
            $output->writeln('<error>Failed to write export to "' . $path . '": '
                . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Exported %d audit event(s) to %s.</info>', count($rows), $path));

        return Command::SUCCESS;
    }

    /**
     * Collect the filtered audit events as ordered associative rows.
     *
     * @param AuditExportFilter $filter
     * @return array<int, array<string, mixed>>
     */
    private function collectRows(AuditExportFilter $filter): array
    {
        if ($filter->getFrom() !== null) {
            $this->searchCriteriaBuilder->addFilter(AuditEventInterface::CREATED_AT, $filter->getFrom(), 'gteq');
        }
        if ($filter->getTo() !== null) {
            $this->searchCriteriaBuilder->addFilter(AuditEventInterface::CREATED_AT, $filter->getTo(), 'lteq');
        }
        if ($filter->getType() !== null) {
            $this->searchCriteriaBuilder->addFilter(AuditEventInterface::EVENT_TYPE, $filter->getType());
        }
        $this->searchCriteriaBuilder->addSortOrder(
            $this->sortOrderBuilder
                ->setField(AuditEventInterface::CREATED_AT)
                ->setDirection(SortOrder::SORT_ASC)
                ->create()
        );

        $items = $this->auditLog->getList($this->searchCriteriaBuilder->create())->getItems();

        $rows = [];
        foreach ($items as $event) {
            $rows[] = [
                AuditEventInterface::ENTITY_ID => $event->getId(),
                AuditEventInterface::EVENT_TYPE => $event->getEventType(),
                AuditEventInterface::SEVERITY => $event->getSeverity(),
                AuditEventInterface::ACTOR_ADMIN_USER_ID => $event->getActorAdminUserId(),
                AuditEventInterface::TARGET_ADMIN_USER_ID => $event->getTargetAdminUserId(),
                AuditEventInterface::IP => $event->getIp(),
                AuditEventInterface::USER_AGENT => $event->getUserAgent(),
                AuditEventInterface::SUPPORT_REFERENCE_ID => $event->getSupportReferenceId(),
                AuditEventInterface::METADATA => $event->getMetadata(),
                AuditEventInterface::CREATED_AT => $event->getCreatedAt(),
            ];
        }

        return $rows;
    }

    /**
     * Render rows as CSV using the fixed column order.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return string
     */
    private function toCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fputcsv($handle, self::COLUMNS);
        foreach ($rows as $row) {
            $line = [];
            foreach (self::COLUMNS as $column) {
                $line[] = (string) ($row[$column] ?? '');
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content === false ? '' : $content;
    }

    /**
     * Cast a raw option value to a non-empty string, or null.
     *
     * @param mixed $value
     * @return string|null
     */
    private function optionAsString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
