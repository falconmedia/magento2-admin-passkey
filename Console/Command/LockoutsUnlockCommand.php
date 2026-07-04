<?php
/**
 * Copyright (c) FalconMedia. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace FalconMedia\AdminPasskey\Console\Command;

use FalconMedia\AdminPasskey\Api\Data\LockoutInterface;
use FalconMedia\AdminPasskey\Api\LockoutRepositoryInterface;
use FalconMedia\AdminPasskey\Model\Lockout\LockoutManagerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Releases the active lockout for an admin id or username.
 *
 * Resolves the identity to its active lockout row via
 * {@see LockoutRepositoryInterface}, then releases it through
 * {@see LockoutManagerInterface::unlock()} so the release is audited.
 */
class LockoutsUnlockCommand extends Command
{
    private const ARGUMENT_IDENTITY = 'identity';

    public function __construct(
        private readonly LockoutRepositoryInterface $lockoutRepository,
        private readonly LockoutManagerInterface $lockoutManager,
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
        $this->setName('adminpasskey:lockouts:unlock');
        $this->setDescription('Release the active lockout for an admin id or username.');
        $this->addArgument(
            self::ARGUMENT_IDENTITY,
            InputArgument::REQUIRED,
            'Admin user id (numeric) or username of the locked account.'
        );
        $this->setHelp(
            'Finds the active lockout for the given admin id or username and releases it (audited).'
            . ' Returns exit code 1 when there is no active lockout for the identity.'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $identity = trim((string) $input->getArgument(self::ARGUMENT_IDENTITY));
        if ($identity === '') {
            $output->writeln('<error>An admin id or username is required.</error>');

            return Command::INVALID;
        }

        $lockout = $this->resolveLockout($identity);
        if ($lockout === null || $lockout->getId() === null) {
            $output->writeln(sprintf('<error>No active lockout found for "%s".</error>', $identity));

            return Command::FAILURE;
        }

        try {
            $entityId = (int) $lockout->getId();
            $this->appState->emulateAreaCode(
                Area::AREA_ADMINHTML,
                function () use ($entityId): void {
                    $this->lockoutManager->unlock($entityId);
                }
            );
        } catch (\Throwable $exception) {
            $output->writeln('<error>Failed to release lockout: ' . $exception->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Released lockout #%d for "%s".</info>', (int) $lockout->getId(), $identity));

        return Command::SUCCESS;
    }

    /**
     * Resolve the active lockout for a numeric admin id or a username.
     *
     * @param string $identity
     * @return LockoutInterface|null
     */
    private function resolveLockout(string $identity): ?LockoutInterface
    {
        if (ctype_digit($identity)) {
            $byAdmin = $this->lockoutRepository->findActiveForAdmin((int) $identity);
            if ($byAdmin !== null) {
                return $byAdmin;
            }
        }

        return $this->lockoutRepository->findActiveForUsername($identity);
    }
}
