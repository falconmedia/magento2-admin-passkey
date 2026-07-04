<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Console\OutputFormatter;
use FalconMedia\AdminPasskey\Model\Migration\AdoptionStatsProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints the passkey-adoption summary across admin users.
 *
 * Delegates to {@see AdoptionStatsProvider}; read-only. Always exits 0.
 */
class MigrationStatusCommand extends Command
{
    private const OPTION_FORMAT = 'format';
    private const OPTION_ALL = 'all';

    public function __construct(
        private readonly AdoptionStatsProvider $adoptionStatsProvider,
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
        $this->setName('adminpasskey:migration:status');
        $this->setDescription('Print the passkey-adoption summary across admin users.');
        $this->addOption(
            self::OPTION_FORMAT,
            null,
            InputOption::VALUE_REQUIRED,
            'Output format: table or json.',
            OutputFormatter::FORMAT_TABLE
        );
        $this->addOption(
            self::OPTION_ALL,
            null,
            InputOption::VALUE_NONE,
            'Include inactive admin accounts (default: active only).'
        );
        $this->setHelp(
            'Prints total admins, passkey adoption (with/without and percentage) and native 2FA counts.'
            . ' By default only active admin accounts are counted; pass --all to include inactive ones.'
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

        $activeOnly = !$input->getOption(self::OPTION_ALL);
        $stats = $this->adoptionStatsProvider->getStats($activeOnly);

        if ($this->formatter->isJson($format)) {
            $data = $stats->toArray();
            $data['scope'] = $activeOnly ? 'active' : 'all';
            $output->writeln($this->formatter->toJson($data));

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Scope: %s admin accounts', $activeOnly ? 'active' : 'all'));
        $this->formatter->renderTable(
            $output,
            ['Metric', 'Value'],
            [
                ['Total admins', (string) $stats->getTotalAdmins()],
                ['With passkey', (string) $stats->getWithPasskey()],
                ['Without passkey', (string) $stats->getWithoutPasskey()],
                ['Adoption %', number_format($stats->getAdoptionPercent(), 1)],
                ['2FA enabled globally', $stats->isTwoFaEnabledGlobally() ? 'yes' : 'no'],
                ['2FA active', (string) $stats->getTwoFaActive()],
                ['2FA configured', (string) $stats->getTwoFaConfigured()],
                ['2FA none', (string) $stats->getTwoFaNone()],
                ['2FA disabled', (string) $stats->getTwoFaDisabled()],
            ]
        );

        return Command::SUCCESS;
    }
}
