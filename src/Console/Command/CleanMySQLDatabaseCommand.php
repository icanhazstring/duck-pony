<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use duckpony\UseCase\FetchDatabasesByPatternUseCase;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Zend\Config\Config;

class CleanMySQLDatabaseCommand extends Command
{
    use StatusesAwareCommandTrait;

    private const DATABASE_BLACKLIST = [
        'information_schema',
        'perfomance_schema',
        'mysql',
    ];

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

    public function __construct(
        LoggerInterface $logger,
        IssueService $issueService,
        Config $config,
        PDO $pdo,
        FetchDatabasesByPatternUseCase $fetchDatabasesByPatternUseCase
    ) {
        parent::__construct('db:clean');
        $this->logger = $logger;
        $this->issueService = $issueService;
        $this->config = $config->get(self::class);
        $this->pdo = $pdo;
        $this->fetchDatabasesByPatternUseCase = $fetchDatabasesByPatternUseCase;
    }

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');
        $this->addArgument('branchname-filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Filter branchname');

        $this->setDescription('Scans Database and cleans orphaned')
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

        $statuses = $this->initStatuses($input, $io);

        $invert = $input->getOption('invert');
        $pattern = $input->getOption('pattern');
        $branchNameFilter = $input->getArgument('branchname-filter');

        if (!isset($this->config->username, $this->config->password, $this->config->hostname)) {
            throw new RuntimeException('MySQL config is incorrect! Username, password and hostname required!');
        }

        $io->title('Scan databases');

        $databases = $this->fetchDatabasesByPatternUseCase->execute($pattern);
        $remove = [];

        foreach ($databases as $database) {
            $branchName = !empty($branchNameFilter)
                ? str_replace($branchNameFilter, '', $database)
                : $database;

            $issue = null;
            try {
                $issue = $this->issueService->get($branchName, ['fields' => ['status']]);
            } catch (JiraException $e) {
                $this->logger->critical(
                    'While cleaning up, I found an unexpected database with no matching Jira Ticket.'
                    . ' The database will be removed, but you should investigate why it was'
                    . ' created in the first place!',
                    [
                        'CleanMySQLDatabaseName' => 'Error while matching databases to issues',
                        'Unexpected database'    => $database,
                        'Branch name'            => $branchName,
                        'Possible cause'         => 'Jira ticket was deleted'
                    ]
                );
            }

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
            if (in_array($db, self::DATABASE_BLACKLIST, true)) {
                throw new RuntimeException(sprintf('Database %s is blacklisted!', $db));
            }

            $stmt = $this->pdo->prepare(sprintf('DROP DATABASE IF EXISTS `%s`', $db));

            try {
                $result = $stmt->execute();
                if (!$result) {
                    $this->logger->critical(
                        'Dropping the database failed',
                        [
                            'CleanMySQLDatabaseName' => 'Error deleting a database!',
                            'Unexpected database'    => $db,
                            'PDO ErrorInfo'          => $this->pdo->errorInfo()
                        ]
                    );
                }
            } catch (Throwable $e) {
                $this->logger->critical(
                    'Dropping the database failed',
                    [
                        'CleanMySQLDatabaseName' => 'Error deleting a database!',
                        'Unexpected database'    => $db,
                        'Errormessage'           => $e->getMessage(),
                        'StackTrace'             => $e->getTraceAsString()
                    ]
                );
            }
            /** @noinspection DisconnectedForeachInstructionInspection */
            $io->progressAdvance();
        }

        $io->progressFinish();

        return 0;
    }
}
