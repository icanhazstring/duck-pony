<?php
declare(strict_types=1);

namespace duckpony\Console\Command;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CleanMySQLDatabaseCommand
 *
 * @package duckpony\Console\Command
 * @author  Michel Petiton <michel.petiton@check24.de>
 */
class CleanMySQLDatabaseCommand extends AbstractCommand
{
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
    protected function configure()
    {
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Config', $this->getRootPath() . '/config/config.yml');
        $this->addArgument('branchname-filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL , 'Filter branchname');

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
     * @return int|null|void
     * @throws \JiraRestApi\JiraException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $statuses = explode(',', $input->getOption('status'));
        $statuses = array_map('trim', $statuses);
        $statuses = array_map('strtolower', $statuses);

        $invert = $input->getOption('invert');
        $config = Yaml::parse(file_get_contents($input->getOption('config')));
        $pattern = $input->getOption('pattern');
        $branchNameFilter = $input->getArgument('branchname-filter');

        if (!isset(
            $config['MySQL']['username'],
            $config['MySQL']['password'],
            $config['MySQL']['hostname']
        )) {
            throw new \RuntimeException('MySQL config is incorrect! Username password and hostname required!');
        }

        $dbUser = $config['MySQL']['username'];
        $dbPassword = $config['MySQL']['password'];
        $dbHost = $config['MySQL']['hostname'];

        try {
            $issueService = new IssueService(new ArrayConfiguration([
                'jiraHost'     => $config['Jira']['hostname'],
                'jiraUser'     => $config['Jira']['username'],
                'jiraPassword' => $config['Jira']['password']
            ]));
        } catch (JiraException|\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->title('Scan databases');

        $connectionString = sprintf('mysql:host=%s;', $dbHost);
        $pdo = new \PDO($connectionString, $dbUser, $dbPassword);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $databases = array_column($pdo->query('SHOW DATABASES')->fetchAll(), 'Database');
        $databases = array_filter(
            $databases,
            function ($dbName) use ($pattern) {
                return preg_match($pattern, $dbName);
            }
        );

        $remove = [];
        $notfound = [];

        foreach ($databases as $database) {

            $branchName = !empty($branchNameFilter)
                ? str_replace($branchNameFilter, '', $database)
                : $database;

            try {
                $issue = $issueService->get($branchName, ['fields' => ['status']]);

                if (!empty($statuses)) {
                    $issueStatus = strtolower($issue->fields->status->name);

                    if ($invert) {
                        if (!in_array($issueStatus, $statuses)) {
                            $remove[] = $database;
                            $io->text(sprintf('Found orphaned database %s', $database));
                        }
                    } else {
                        if (in_array($issueStatus, $statuses)) {
                            $remove[] = $database;
                            $io->text(sprintf('Found orphaned database %s', $database));
                        }
                    }
                }
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                $notfound[] = $database;
            }
        }

        if (empty($remove)) {
            $io->title('Found no databases to remove!');
            return;
        }

        $io->title('Remove matching databases');
        $io->progressStart(count($remove));

        foreach ($remove as $db) {

            if (in_array($db, self::DATABASE_BLACKLIST, true)) {
                throw new \RuntimeException(sprintf('Database %s is blacklisted!', $db));
            }

            $stmt = $pdo->prepare(sprintf('DROP DATABASE IF EXISTS `%s`', $db));

            try {
                $result = $stmt->execute();
                if (!$result) {
                    var_dump($pdo->errorInfo());
                }
            } catch (\Throwable $e) {
                var_dump($e);
            }
            $io->progressAdvance();
        }

        $io->progressFinish();
    }
}
