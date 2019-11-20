<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

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

    protected function configure()
    {
        $this->configurePatternOption();

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

        if (!$pattern) {
            $io->error('Missing pattern option.');
            die(1);
        }

        $databases = $this->fetchDatabasesByPatternUseCase->execute($pattern);

        foreach ($databases as $database) {
            $this->dropDatabaseUseCase->execute($database);
        }

        return 0;
    }
}
