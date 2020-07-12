<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use duckpony\Console\Command\Option\DryRunOptionTrait;
use duckpony\Console\Command\Option\HostNameOptionTrait;
use duckpony\Console\Command\Option\PatternOptionTrait;
use duckpony\UseCase\DropDatabaseUseCase;
use duckpony\UseCase\FetchDatabasesByPatternUseCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PurgeDatabaseCommand extends Command
{
    use PatternOptionTrait;
    use DryRunOptionTrait;
    use HostNameOptionTrait;

    /** @var FetchDatabasesByPatternUseCase */
    private $fetchDatabasesByPatternUseCase;
    /** @var DropDatabaseUseCase */
    private $dropDatabaseUseCase;

    public function __construct(
        FetchDatabasesByPatternUseCase $fetchDatabasesByPatternUseCase,
        DropDatabaseUseCase $dropDatabaseUseCase
    ) {
        parent::__construct('purge:database');
        $this->fetchDatabasesByPatternUseCase = $fetchDatabasesByPatternUseCase;
        $this->dropDatabaseUseCase = $dropDatabaseUseCase;
    }

    protected function configure(): void
    {
        $this->configurePatternOption();
        $this->configureDryRunOption();
        $this->configureHostNameOption();

        $this->setDescription('Purge databases based on given pattern')
            ->setHelp(
                <<<EOT
Disables and stops systemd services that have
no reference folder in given folder argument
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pattern = $this->getPattern($input);
        $hostName = $this->getHostName($input);
        $isDryRun = $this->isDryRun($input);

        $canRunOnHost = $this->canRunOnHost($hostName, $io);

        if ($canRunOnHost === false) {
            $io->note('You are running this command on a non master host and have configured to in this case. ' .
                'Please make sure you are on master or configure the host_name option.');
            die(0);
        }

        if (!$pattern) {
            $io->error('Missing pattern option.');
            die(1);
        }

        $databases = $this->fetchDatabasesByPatternUseCase->execute($pattern);

        $io->title(
            sprintf('Scan for databases using pattern "%s".', $pattern)
        );

        $io->writeln(
            sprintf('Found %d databases to remove', count($databases)),
            OutputInterface::VERBOSITY_DEBUG
        );

        $progressBar = $io->createProgressBar(count($databases));

        if ($isDryRun) {
            $io->note('DRY-RUN enabled, nothing will be dropped');
        }

        foreach ($databases as $database) {
            if (!$isDryRun) {
                $this->dropDatabaseUseCase->execute($database);
                /** @noinspection DisconnectedForeachInstructionInspection */
                $progressBar->advance();
            } else {
                $io->writeln(
                    sprintf('<fg=cyan>Would delete:</> %s', $database)
                );
            }
        }

        if (!$isDryRun) {
            $progressBar->finish();
        }

        $io->newLine();

        return 0;
    }
}
