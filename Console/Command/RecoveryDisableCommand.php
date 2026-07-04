<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Model\Recovery\RecoveryModeServiceInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Disables emergency recovery mode.
 *
 * Delegates to {@see RecoveryModeServiceInterface::disable()} which persists and
 * audits the state change. Runs in the adminhtml area for consistent auditing.
 */
class RecoveryDisableCommand extends Command
{
    private const OPTION_REASON = 'reason';

    public function __construct(
        private readonly RecoveryModeServiceInterface $recoveryModeService,
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
        $this->setName('adminpasskey:recovery:disable');
        $this->setDescription('Disable emergency recovery mode (audited).');
        $this->addOption(
            self::OPTION_REASON,
            null,
            InputOption::VALUE_REQUIRED,
            'Optional non-sensitive reason recorded with the state change.'
        );
        $this->setHelp(
            'Disables emergency recovery mode and records an audited state change. Returns exit code 1'
            . ' when recovery mode is not currently active.'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reasonOption = $input->getOption(self::OPTION_REASON);
        $reason = is_string($reasonOption) && trim($reasonOption) !== '' ? trim($reasonOption) : null;

        try {
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                function () use ($reason): void {
                    $this->recoveryModeService->disable($reason);
                }
            );
        } catch (LocalizedException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        } catch (\Throwable $exception) {
            $output->writeln('<error>Failed to disable recovery mode: ' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Emergency recovery mode disabled.</info>');

        return Command::SUCCESS;
    }
}
