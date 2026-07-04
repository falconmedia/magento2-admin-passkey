<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Model\Diagnostics\DiagnosticsServiceInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates a support diagnostics ZIP bundle.
 *
 * Delegates to {@see DiagnosticsServiceInterface} and prints the Support
 * Reference ID plus the absolute archive path. Runs in the adminhtml area so the
 * generation is audited consistently with the Admin UI action.
 */
class DiagnosticsGenerateCommand extends Command
{
    public function __construct(
        private readonly DiagnosticsServiceInterface $diagnosticsService,
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
        $this->setName('adminpasskey:diagnostics:generate');
        $this->setDescription('Generate a support diagnostics ZIP bundle and print its reference and path.');
        $this->setHelp(
            'Generates a redacted diagnostics ZIP bundle, records the report, and prints the Support'
            . ' Reference ID together with the absolute archive path. Returns exit code 1 on failure.'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $report = $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                fn () => $this->diagnosticsService->generate()
            );
            $path = $this->diagnosticsService->getReportArchivePath((int) $report->getId());
        } catch (\Throwable $exception) {
            $output->writeln('<error>Failed to generate diagnostics bundle: ' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Support Reference ID:</info> %s', (string) $report->getSupportReferenceId()));
        $output->writeln(sprintf('<info>Archive path:</info> %s', $path));

        return Command::SUCCESS;
    }
}
