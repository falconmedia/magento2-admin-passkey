<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Console\OutputFormatter;
use FalconMedia\AdminPasskey\Model\Health\HealthCheckServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs the AdminPasskey environment/configuration health checks.
 *
 * Delegates entirely to {@see HealthCheckServiceInterface}; exits non-zero when
 * any check reports an error so it can gate deploy/monitoring pipelines.
 */
class HealthCommand extends Command
{
    private const OPTION_FORMAT = 'format';

    public function __construct(
        private readonly HealthCheckServiceInterface $healthCheckService,
        private readonly OutputFormatter $formatter,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('adminpasskey:health');
        $this->setDescription('Run the AdminPasskey health checks (exit code 1 if any check fails).');
        $this->addOption(
            self::OPTION_FORMAT,
            null,
            InputOption::VALUE_REQUIRED,
            'Output format: table or json.',
            OutputFormatter::FORMAT_TABLE
        );
        $this->setHelp(
            'Runs every registered health check and prints the results. Returns exit code 0 when all'
            . ' checks pass or only warn, and exit code 1 when at least one check reports an error.'
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

        $report = $this->healthCheckService->run();

        if ($this->formatter->isJson($format)) {
            $output->writeln(
                $this->formatter->toJson(
                    [
                        'overall_status' => $report->getOverallStatus(),
                        'errors' => $report->getErrorCount(),
                        'warnings' => $report->getWarningCount(),
                        'checks' => $report->toArray(),
                    ]
                )
            );
        } else {
            $rows = [];
            foreach ($report->getResults() as $result) {
                $rows[] = [$result->getId(), $result->getLabel(), $result->getStatus(), $result->getMessage()];
            }
            $this->formatter->renderTable($output, ['ID', 'Check', 'Status', 'Message'], $rows);
            $output->writeln(
                sprintf(
                    'Overall: %s (%d error(s), %d warning(s))',
                    $report->getOverallStatus(),
                    $report->getErrorCount(),
                    $report->getWarningCount()
                )
            );
        }

        return $report->hasErrors() ? Command::FAILURE : Command::SUCCESS;
    }
}
