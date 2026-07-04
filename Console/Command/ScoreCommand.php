<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Console\OutputFormatter;
use FalconMedia\AdminPasskey\Model\SecurityScore\SecurityScoreServiceInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Computes and prints the current security score.
 *
 * Delegates to {@see SecurityScoreServiceInterface}. With `--snapshot` the score
 * is persisted (and audited) instead of only computed.
 */
class ScoreCommand extends Command
{
    private const OPTION_FORMAT = 'format';
    private const OPTION_SNAPSHOT = 'snapshot';

    public function __construct(
        private readonly SecurityScoreServiceInterface $securityScoreService,
        private readonly OutputFormatter $formatter,
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
        $this->setName('adminpasskey:score');
        $this->setDescription('Compute the AdminPasskey security score, label and recommendations.');
        $this->addOption(
            self::OPTION_FORMAT,
            null,
            InputOption::VALUE_REQUIRED,
            'Output format: table or json.',
            OutputFormatter::FORMAT_TABLE
        );
        $this->addOption(
            self::OPTION_SNAPSHOT,
            null,
            InputOption::VALUE_NONE,
            'Persist the computed score as an audited snapshot.'
        );
        $this->setHelp(
            'Computes the current security score (0-100), its label and improvement recommendations.'
            . ' Pass --snapshot to persist the result as an audited security-score snapshot. Returns exit'
            . ' code 0 on success and 1 when persisting a snapshot fails.'
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

        if ($input->getOption(self::OPTION_SNAPSHOT)) {
            try {
                $snapshot = $this->appState->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    fn () => $this->securityScoreService->snapshot()
                );
            } catch (\Throwable $exception) {
                $output->writeln('<error>Failed to persist the security-score snapshot: '
                    . $exception->getMessage() . '</error>');

                return Command::FAILURE;
            }
            $output->writeln(sprintf('<info>Snapshot #%d persisted.</info>', (int) $snapshot->getId()));
        }

        $result = $this->securityScoreService->compute();

        if ($this->formatter->isJson($format)) {
            $output->writeln(
                $this->formatter->toJson(
                    [
                        'score' => $result->getScore(),
                        'label' => $result->getLabel(),
                        'breakdown' => $result->getBreakdown(),
                        'recommendations' => $result->getRecommendations(),
                    ]
                )
            );

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Security score: %d/100 (%s)', $result->getScore(), $result->getLabel()));

        $breakdownRows = [];
        foreach ($result->getBreakdown() as $category => $value) {
            $breakdownRows[] = [(string) $category, (string) $value];
        }
        if ($breakdownRows !== []) {
            $this->formatter->renderTable($output, ['Category', 'Score'], $breakdownRows);
        }

        $recommendations = $result->getRecommendations();
        if ($recommendations !== []) {
            $output->writeln('Recommendations:');
            foreach ($recommendations as $recommendation) {
                $output->writeln(
                    sprintf('  - [%s] %s', $recommendation['code'], $recommendation['message'])
                );
            }
        }

        return Command::SUCCESS;
    }
}
