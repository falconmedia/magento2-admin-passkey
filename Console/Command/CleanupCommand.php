<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Api\Data\CleanupLogInterface;
use FalconMedia\AdminPasskey\Console\OutputFormatter;
use FalconMedia\AdminPasskey\Model\Cleanup\CleanupServiceInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\Serializer\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs the retention cleanup and prints the per-category counts.
 *
 * Delegates to {@see CleanupServiceInterface}. The service performs a single
 * transactional run across all configured categories; there is no dry-run seam
 * on the contract, so cleanup is always destructive (see help text).
 */
class CleanupCommand extends Command
{
    private const OPTION_FORMAT = 'format';

    public function __construct(
        private readonly CleanupServiceInterface $cleanupService,
        private readonly OutputFormatter $formatter,
        private readonly Json $json,
        private readonly State $appState,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('adminpasskey:cleanup');
        $this->setDescription('Run the AdminPasskey retention cleanup and print the deleted counts.');
        $this->addOption(
            self::OPTION_FORMAT,
            null,
            InputOption::VALUE_REQUIRED,
            'Output format: table or json.',
            OutputFormatter::FORMAT_TABLE
        );
        $this->setHelp(
            'Deletes expired module data (challenges, diagnostics, audit, score snapshots, reminders)'
            . ' according to the configured retention windows and records a cleanup-log entry.'
            . ' NOTE: the cleanup service has no dry-run mode; this command always performs the deletion.'
            . ' Returns exit code 1 when the run is recorded as failed.'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = (string) $input->getOption(self::OPTION_FORMAT);
        try {
            $this->formatter->assertValidFormat($format);
        } catch (\InvalidArgumentException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::INVALID;
        }

        try {
            $log = $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                fn () => $this->cleanupService->run()
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>Cleanup failed: ' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $counts = $this->decodeCounts($log->getCounts());
        $status = (string) $log->getStatus();

        if ($this->formatter->isJson($format)) {
            $output->writeln(
                $this->formatter->toJson(
                    [
                        'status' => $status,
                        'counts' => $counts,
                        'total_deleted' => array_sum($counts),
                    ]
                )
            );
        } else {
            $rows = [];
            foreach ($counts as $category => $count) {
                $rows[] = [(string) $category, (string) $count];
            }
            $this->formatter->renderTable($output, ['Category', 'Deleted'], $rows);
            $output->writeln(sprintf('Status: %s, total deleted: %d', $status, (int) array_sum($counts)));
        }

        return $status === CleanupLogInterface::STATUS_FAILED ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Decode the cleanup-log counts JSON into an integer map.
     *
     * @param string|null $countsJson
     * @return array<string, int>
     */
    private function decodeCounts(?string $countsJson): array
    {
        if ($countsJson === null || $countsJson === '') {
            return [];
        }

        try {
            $decoded = $this->json->unserialize($countsJson);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $counts = [];
        foreach ($decoded as $category => $count) {
            $counts[(string) $category] = (int) $count;
        }

        return $counts;
    }
}
