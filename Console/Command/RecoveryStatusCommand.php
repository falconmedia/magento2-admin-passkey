<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Api\Data\RecoveryStateInterface;
use FalconMedia\AdminPasskey\Console\OutputFormatter;
use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints the current emergency recovery-mode state.
 *
 * Delegates to {@see RecoveryModeServiceInterface}; read-only. Always exits 0.
 */
class RecoveryStatusCommand extends Command
{
    private const OPTION_FORMAT = 'format';

    public function __construct(
        private readonly RecoveryModeServiceInterface $recoveryModeService,
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
        $this->setName('adminpasskey:recovery:status');
        $this->setDescription('Print the current emergency recovery-mode state.');
        $this->addOption(
            self::OPTION_FORMAT,
            null,
            InputOption::VALUE_REQUIRED,
            'Output format: table or json.',
            OutputFormatter::FORMAT_TABLE
        );
        $this->setHelp('Prints whether recovery mode is active and the most recent state record. Always exits 0.');

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

        $active = $this->recoveryModeService->isActive();
        $current = $this->recoveryModeService->getCurrent();

        if ($this->formatter->isJson($format)) {
            $output->writeln(
                $this->formatter->toJson(
                    [
                        'active' => $active,
                        'state' => $current?->getState(),
                        'enabled_at' => $current?->getEnabledAt(),
                        'disabled_at' => $current?->getDisabledAt(),
                        'actor_admin_user_id' => $current?->getActorAdminUserId(),
                        'reason' => $current?->getReason(),
                    ]
                )
            );

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Recovery mode: %s', $active ? 'ACTIVE' : 'inactive'));
        if ($current instanceof RecoveryStateInterface) {
            $this->formatter->renderTable(
                $output,
                ['Field', 'Value'],
                [
                    ['state', (string) $current->getState()],
                    ['enabled_at', (string) ($current->getEnabledAt() ?? '')],
                    ['disabled_at', (string) ($current->getDisabledAt() ?? '')],
                    ['actor_admin_user_id', (string) ($current->getActorAdminUserId() ?? '')],
                    ['reason', (string) ($current->getReason() ?? '')],
                ]
            );
        }

        return Command::SUCCESS;
    }
}
