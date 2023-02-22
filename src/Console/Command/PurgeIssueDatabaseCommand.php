<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use duckpony\Console\Command\Argument\BranchnameFilterArgumentTrait;
use duckpony\Console\Command\Option\HostNameOptionTrait;
use duckpony\Console\Command\Option\PatternOptionTrait;
use duckpony\Console\Command\Option\StatusOptionTrait;
use duckpony\Exception\JiraTicketNotFoundException;
use duckpony\Rule\IsIssueWithGivenStatusRule;
use duckpony\UseCase\DropDatabaseUseCase;
use duckpony\UseCase\FetchDatabasesByPatternUseCase;
use duckpony\UseCase\FetchIssueUseCase;
use PDO;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Laminas\Config\Config;

class PurgeIssueDatabaseCommand extends Command
{
    use AliasDeprecationTrait;

    use BranchnameFilterArgumentTrait;
    use StatusOptionTrait;
    use PatternOptionTrait;
    use HostNameOptionTrait;

    protected const DEPRECATION_ALIAS = 'db:clean';

    /** @var Config */
    private $config;

    /** @var FetchDatabasesByPatternUseCase */
    private $fetchDatabasesByPatternUseCase;
    /** @var DropDatabaseUseCase */
    private $dropDatabaseUseCase;
    /** @var FetchIssueUseCase */
    private $fetchIssueUseCase;
    /** @var IsIssueWithGivenStatusRule */
    private $isIssueWithGivenStatusRule;

    public function __construct(
        Config $config,
        FetchDatabasesByPatternUseCase $fetchDatabasesByPatternUseCase,
        DropDatabaseUseCase $dropDatabaseUseCase,
        FetchIssueUseCase $fetchIssueUseCase,
        IsIssueWithGivenStatusRule $isIssueWithGivenStatusRule
    ) {
        parent::__construct('issue:purge-db');
        $this->config = $config->get(PDO::class);
        $this->fetchDatabasesByPatternUseCase = $fetchDatabasesByPatternUseCase;
        $this->dropDatabaseUseCase = $dropDatabaseUseCase;
        $this->fetchIssueUseCase = $fetchIssueUseCase;
        $this->isIssueWithGivenStatusRule = $isIssueWithGivenStatusRule;
    }

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureStatusOption();
        $this->configureBranchnameFilterArgument();
        $this->configurePatternOption();
        $this->configureHostNameOption();

        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');

        $this->setAliases([self::DEPRECATION_ALIAS])
            ->setDescription('Scans Database and cleans orphaned')
            ->setHelp(
                <<<EOT
Scans MySQL Databases and removes
them under certain conditions
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->checkAliasDeprecation($input, $io);

        $statuses = $this->getStatusList($input, $io);

        $invert = $this->getInvert($input);
        $pattern = $this->getPattern($input);
        $hostName = $this->getHostName($input);
        $branchNameFilter = $this->getBranchnameFilter($input);
        $canRunOnHost = $this->canRunOnHost($hostName, $io);

        if ($canRunOnHost === false) {
            $io->note('You are running this command on master and have configured to stop if so.' .
                ' Please make sure you are on master or configure the host_name option.');
            die(0);
        }

        if (!isset($this->config->username, $this->config->password, $this->config->hostname)) {
            throw new RuntimeException('MySQL config is incorrect! Username, password and hostname required!');
        }

        $io->title('Scan databases');

        $databases = $this->fetchDatabasesByPatternUseCase->execute($pattern);
        $remove = [];

        foreach ($databases as $database) {
            $issue = null;
            try {
                $issue = $this->fetchIssueUseCase->execute($database, $branchNameFilter);
            } catch (JiraTicketNotFoundException $e) {
                $remove[] = $database;
            }

            if ($this->isIssueWithGivenStatusRule->appliesTo($issue, $statuses, $invert)) {
                $remove[] = $database;
            }
        }

        if (empty($remove)) {
            $io->title('Found no databases to remove!');

            return 0;
        }

        $io->title('Remove matching databases');
        $io->progressStart(count($remove));

        foreach ($remove as $db) {
            $this->dropDatabaseUseCase->execute($db);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $io->progressAdvance();
        }

        $io->progressFinish();

        return 0;
    }
}
