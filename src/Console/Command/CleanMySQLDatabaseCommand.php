<?php
declare(strict_types=1);

namespace duckpony\Console\Command;

use Exception;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class CleanMySQLDatabaseCommand extends AbstractCommand
{
    use StatusesAwareCommandTrait;

    private const DATABASE_BLACKLIST = [
        'information_schema',
        'perfomance_schema',
        'mysql',
    ];

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');
        $this->addArgument('branchname-filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Filter branchname');

        $this->setName('db:clean')
            ->setDescription('Scans Database and cleans orphaned')
            ->setHelp(
                <<<EOT
Scans MySQL Databases and removes
them under certain conditions
EOT
            );
    }

    /**
     * Executes the console command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function executeWithConfig(
        InputInterface $input,
        OutputInterface $output,
        LoggerInterface $logger,
        array $config
    ): int {
        $io = new SymfonyStyle($input, $output);

        $statuses = $this->initStatuses($input, $io);

        $invert           = $input->getOption('invert');
        $pattern          = $input->getOption('pattern');
        $branchNameFilter = $input->getArgument('branchname-filter');

        if (!isset(
            $config['MySQL']['username'],
            $config['MySQL']['password'],
            $config['MySQL']['hostname']
        )) {
            throw new RuntimeException('MySQL config is incorrect! Username, password and hostname required!');
        }

        $dbUser     = $config['MySQL']['username'];
        $dbPassword = $config['MySQL']['password'];
        $dbHost     = $config['MySQL']['hostname'];

        try {
            $issueService = new IssueService(new ArrayConfiguration([
                'jiraHost'     => $config['Jira']['hostname'],
                'jiraUser'     => $config['Jira']['username'],
                'jiraPassword' => $config['Jira']['password']
            ]));
        } catch (JiraException|Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->title('Scan databases');

        $connectionString = sprintf('mysql:host=%s;', $dbHost);
        $pdo              = new PDO($connectionString, $dbUser, $dbPassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $databases = array_column($pdo->query('SHOW DATABASES')->fetchAll(), 'Database');
        $databases = array_filter(
            $databases,
            static function ($dbName) use ($pattern) {
                return preg_match($pattern, $dbName);
            }
        );

        $remove = [];

        foreach ($databases as $database) {

            $branchName = !empty($branchNameFilter)
                ? str_replace($branchNameFilter, '', $database)
                : $database;

            $issue = null;
            try {
                $issue = $issueService->get($branchName, ['fields' => ['status']]);
            } catch (Exception $e) {
                if ($e->getCode() === 404) {
                    $logger->critical(
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
                    $remove[] = $database;
                    $io->text(sprintf('Found unexpected database %s', $database));
                } else {
                    throw $e;
                }
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

            $stmt = $pdo->prepare(sprintf('DROP DATABASE IF EXISTS `%s`', $db));

            try {
                $result = $stmt->execute();
                if (!$result) {
                    $logger->critical(
                        'Dropping the database failed',
                        [
                            'CleanMySQLDatabaseName' => 'Error deleting a database!',
                            'Unexpected database'    => $db,
                            'PDO ErrorInfo'          => $pdo->errorInfo()
                        ]
                    );
                }
            } catch (Throwable $e) {
                $logger->critical(
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
