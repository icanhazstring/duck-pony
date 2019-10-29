<?php
declare(strict_types=1);

namespace duckpony\Console\Command;

use Exception;
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

class CleanBranchCommand extends AbstractCommand
{
    use StatusesAwareCommandTrait;
    use SlackLoggerAwareTrait;

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument('folder', InputArgument::REQUIRED, 'Folder');
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');
        $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Confirm questions with yes');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force delete');
        $this->addArgument('branchname-filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Filter branchname');

        $this->setName('folder:clean')
            ->setDescription('Scan folder and clean branches')
            ->setHelp(
                <<<EOT
Scan folder iterate over sub folders and removes
them under certain conditions
EOT
            );
    }

    protected function executeWithConfig(
        InputInterface $input,
        OutputInterface $output,
        array $config
    ): int {
        $io = new SymfonyStyle($input, $output);

        $statuses = $this->initStatuses($input, $io);

        $folder = $input->getArgument('folder');
        $folder = stream_resolve_include_path($folder);

        $invert           = $input->getOption('invert');
        $force            = $input->getOption('force');
        $yes              = $input->getOption('yes') ? : $force;
        $pattern          = $input->getOption('pattern') ?? $config['CleanBranch']['pattern'];
        $branchNameFilter = $input->getArgument('branchname-filter');

        $finder      = new Finder();
        $directories = $finder->depth(0)->directories()->in($folder);

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

        $io->title('Scan folder ' . $folder . ' for outdated issues');

        $dirCount = $directories->count();
        $io->writeln(
            sprintf('Found %d folders to check', $dirCount),
            OutputInterface::VERBOSITY_DEBUG
        );

        $fs       = new Filesystem();
        $remove   = [];
        $notfound = [];

        // If we don't force cleanup, filter out non matching branches
        if (!$force) {
            // Filter using pattern
            $directories->filter(static function (\SplFileInfo $dir) use ($pattern, $io) {

                $matches = (bool)preg_match($pattern, $dir->getFilename());

                if (!$matches) {
                    $io->writeln(
                        sprintf('%s not matching pattern %s', $dir->getFilename(), $pattern),
                        OutputInterface::VERBOSITY_DEBUG
                    );
                }

                return $matches;
            });
        }

        $io->writeln(
            sprintf('Filtered %d files not matching pattern %s', $dirCount - $directories->count(), $pattern),
            OutputInterface::VERBOSITY_DEBUG
        );

        $progressBar = $io->createProgressBar(count($directories->directories()));

        foreach ($directories->directories() as $index => $dir) {
            /* @var SplFileInfo $dir */
            $branchName = !empty($branchNameFilter)
                ? str_replace($branchNameFilter, '', $dir->getFilename())
                : $dir->getFilename();

            $issue = null;
            try {
                $issue = $issueService->get($branchName, ['fields' => ['status']]);
            } catch (JiraException $e) {
                if ($e->getCode() === 404) {
                    $this->logger->critical(
                        'While cleaning up, I found an unexpected subfolder with no matching Jira Ticket.'
                        . ' The folder will be removed, but you should investigate why it was'
                        . ' created in the first place!',
                        [
                            'CleanBranchName'           => 'Error while matching directories to issues',
                            'Folder to clean up'        => $folder,
                            'Unexpected folder content' => $dir->getFilename(),
                            'Possible cause'            => 'Jira ticket was deleted'
                        ]
                    );
                } else {
                    throw $e;
                }
            }

            if($issue) {
                $issueStatus = strtolower($issue->fields->status->name);

                $statusFound = in_array($issueStatus, $statuses, true);
                if ($invert) {
                    if (!$statusFound) {
                        $remove[] = $dir;
                    }
                } elseif ($statusFound) {
                    $remove[] = $dir;
                }
            } else {
                $notfound[] = $dir;
            }

            /** @noinspection DisconnectedForeachInstructionInspection */
            $progressBar->advance();
        }

        $progressBar->finish();

        $io->title('Remove matching branches');
        $progressBar = $io->createProgressBar(count($remove));

        foreach ($remove as $index => $dir) {
            $fs->remove($dir->getRealPath());
            $progressBar->setProgress((int)$index);
        }

        $progressBar->finish();

        if (count($notfound) > 0) {
            $io->section('Found some branches that does not exist (anymore)');
            $delete = $yes || $io->confirm('Delete?', false);

            if ($delete) {
                foreach ($notfound as $dir) {
                    $fs->remove($dir->getRealPath());
                }
            }
        }

        return 0;
    }
}
