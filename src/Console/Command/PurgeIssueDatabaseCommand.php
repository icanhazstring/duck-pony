<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use duckpony\Console\Command\Argument\BranchnameFilterArgumentTrait;
use duckpony\Console\Command\Option\PatternOptionTrait;
use duckpony\Console\Command\Option\StatusOptionTrait;
use duckpony\UseCase\DropDatabaseUseCase;
use duckpony\UseCase\FetchDatabasesByPatternUseCase;
use duckpony\UseCase\FetchIssueUseCase;
use JiraRestApi\Issue\IssueService;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zend\Config\Config;

class PurgeIssueDatabaseCommand extends Command
{
    use AliasDeprecationTrait;

    use BranchnameFilterArgumentTrait;
    use StatusOptionTrait;
    use PatternOptionTrait;

    protected const DEPRECATION_ALIAS = 'db:clean';

    /** @var LoggerInterface */
    private $logger;
    /** @var IssueService */
    private $issueService;
    /** @var Config */
    private $config;
    /** @var PDO */
    private $pdo;
    /** @var FetchDatabasesByPatternUseCase */
    private $fetchDatabasesByPatternUseCase;
    /** @var DropDatabaseUseCase */
    private $dropDatabaseUseCase;
    /** @var FetchIssueUseCase */
    private $fetchIssueUseCase;

    public function __construct(
        Config $config,
        FetchDatabasesByPatternUseCase $fetchDatabasesByPatternUseCase,
        DropDatabaseUseCase $dropDatabaseUseCase,
        FetchIssueUseCase $fetchIssueUseCase
    ) {
        parent::__construct('issue:purge-db');
        $this->config = $config->get(PDO::class);
        $this->fetchDatabasesByPatternUseCase = $fetchDatabasesByPatternUseCase;
        $this->dropDatabaseUseCase = $dropDatabaseUseCase;
        $this->fetchIssueUseCase = $fetchIssueUseCase;
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
        $branchNameFilter = $this->getBranchnameFilter($input);

        if (!isset($this->config->username, $this->config->password, $this->config->hostname)) {
            throw new RuntimeException('MySQL config is incorrect! Username, password and hostname required!');
        }

        $io->title('Scan databases');

        $databases = $this->fetchDatabasesByPatternUseCase->execute($pattern);
        $remove = [];

        foreach ($databases as $database) {
            $issue = $this->fetchIssueUseCase->execute($database, $branchNameFilter);

            if (!empty($statuses) && $issue) {
                $issueStatus = strtolower($issue->fields->status->name);

                $statusFound = in_array($issueStatus, $statuses, true);
                if ($invert) {
                    if (!$statusFound) {
                        $remove[] = $database;
                        $io->text(sprintf('Found orphaned database %s', $database));
                    }
                } elseif ($statusFound) {
                    $remove[] = $database;
                    $io->text(sprintf('Found orphaned database %s', $database));
                }
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
