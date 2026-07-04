<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Console\OutputFormatter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists the currently active admin lockouts.
 *
 * Delegates to {@see LockoutRepositoryInterface}; read-only. Always exits 0.
 */
class LockoutsListCommand extends Command
{
    private const OPTION_FORMAT = 'format';

    public function __construct(
        private readonly LockoutRepositoryInterface $lockoutRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
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
        $this->setName('adminpasskey:lockouts:list');
        $this->setDescription('List the currently active admin lockouts.');
        $this->addOption(
            self::OPTION_FORMAT,
            null,
            InputOption::VALUE_REQUIRED,
            'Output format: table or json.',
            OutputFormatter::FORMAT_TABLE
        );
        $this->setHelp('Lists every active lockout row. Read-only; always returns exit code 0.');

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

        $criteria = $this->searchCriteriaBuilder
            ->addFilter(LockoutInterface::STATUS, LockoutInterface::STATUS_ACTIVE)
            ->create();

        $items = $this->lockoutRepository->getList($criteria)->getItems();

        if ($this->formatter->isJson($format)) {
            $data = [];
            foreach ($items as $lockout) {
                $data[] = $this->toRowArray($lockout);
            }
            $output->writeln($this->formatter->toJson($data));

            return Command::SUCCESS;
        }

        if ($items === []) {
            $output->writeln('<info>No active lockouts.</info>');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($items as $lockout) {
            $rows[] = [
                (string) $lockout->getId(),
                (string) ($lockout->getAdminUserId() ?? ''),
                (string) ($lockout->getUsername() ?? ''),
                (string) ($lockout->getIp() ?? ''),
                (string) ($lockout->getReason() ?? ''),
                (string) ($lockout->getFailedAttempts() ?? 0),
                (string) ($lockout->getLockedUntil() ?? ''),
            ];
        }
        $this->formatter->renderTable(
            $output,
            ['ID', 'Admin ID', 'Username', 'IP', 'Reason', 'Failed', 'Locked Until'],
            $rows
        );

        return Command::SUCCESS;
    }

    /**
     * Map a lockout to a JSON-friendly row.
     *
     * @param LockoutInterface $lockout
     * @return array<string, mixed>
     */
    private function toRowArray(LockoutInterface $lockout): array
    {
        return [
            'entity_id' => $lockout->getId(),
            'admin_user_id' => $lockout->getAdminUserId(),
            'username' => $lockout->getUsername(),
            'ip' => $lockout->getIp(),
            'reason' => $lockout->getReason(),
            'failed_attempts' => $lockout->getFailedAttempts(),
            'locked_until' => $lockout->getLockedUntil(),
            'created_at' => $lockout->getCreatedAt(),
        ];
    }
}
