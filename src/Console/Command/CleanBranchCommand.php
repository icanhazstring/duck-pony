<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use duckpony\Service\FilterSubFoldersService;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Zend\Config\Config;

class CleanBranchCommand extends Command
{
    use StatusesAwareCommandTrait;

    /** @var FilterSubFoldersService */
    private $filterSubFoldersService;
    /** @var LoggerInterface */
    private $logger;
    /** @var Config */
    private $config;
    /** @var IssueService */
    private $issueService;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        FilterSubFoldersService $filterSubFoldersService,
        IssueService $issueService
    ) {
        parent::__construct('folder:clean');

        $this->logger = $logger;
        $this->config = $config->get(self::class);
        $this->filterSubFoldersService = $filterSubFoldersService;
        $this->issueService = $issueService;
    }

    protected function configure(): void
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'Folder');
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');
        $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Confirm questions with yes');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force delete');
        $this->addArgument('branchname-filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Filter branchname');

        $this->setDescription('Scan folder and clean branches')
            ->setHelp(
                <<<EOT
Scan folder iterate over sub folders and removes
them under certain conditions
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $statuses = $this->initStatuses($input, $io);

        $folder = $input->getArgument('folder');
        $folder = stream_resolve_include_path($folder);

        $invert = $input->getOption('invert');
        $force = $input->getOption('force');
        $yes = $input->getOption('yes') ?: $force;
        $pattern = $input->getOption('pattern') ?? $this->config->pattern;
        $branchNameFilter = $input->getArgument('branchname-filter');

        $finder = new Finder();
        $directories = $finder->depth(0)->directories()->in($folder);

        $io->title('Scan folder ' . $folder . ' for outdated issues');

        $io->writeln(
            sprintf('Found %d folders to check', $directories->count()),
            OutputInterface::VERBOSITY_DEBUG
        );

        // If we don't force cleanup, filter out non matching branches
        if (!$force) {
            ($this->filterSubFoldersService)($directories, $pattern, $io);
        }

        [$remove, $notfound] = $this->findBranchesToCleanup(
            $io,
            $directories,
            $branchNameFilter,
            $this->issueService,
            $folder,
            $statuses,
            $invert
        );

        $this->removeMatchingBranches($remove, $io);

        $this->removeNotFound($notfound, $io, $yes);

        return 0;
    }

    /**
     * @param SymfonyStyle $io
     * @param Finder       $directories
     * @param array        $branchNameFilter
     * @param IssueService $issueService
     * @param string       $folder
     * @param array        $statuses
     * @param bool         $invert
     * @return array
     * @throws \JsonMapper_Exception
     */
    protected function findBranchesToCleanup(
        SymfonyStyle $io,
        Finder $directories,
        array $branchNameFilter,
        IssueService $issueService,
        string $folder,
        array $statuses,
        bool $invert
    ): array {
        $progressBar = $io->createProgressBar(count($directories->directories()));

        $remove = [];
        $notfound = [];

        foreach ($directories->directories() as $index => $dir) {
            /* @var SplFileInfo $dir */
            $branchName = !empty($branchNameFilter)
                ? str_replace($branchNameFilter, '', $dir->getFilename())
                : $dir->getFilename();

            $issue = null;
            try {
                $issue = $issueService->get($branchName, ['fields' => ['status']]);
            } catch (JiraException $e) {
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
            }

            if ($issue) {
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

        return [$remove, $notfound];
    }

    /**
     * @param SplFileInfo[] $remove
     * @param SymfonyStyle  $io
     */
    protected function removeMatchingBranches(array $remove, SymfonyStyle $io): void
    {
        $fs = new Filesystem();

        $io->title('Remove matching branches');
        $progressBar = $io->createProgressBar(count($remove));

        foreach ($remove as $index => $dir) {
            $fs->remove($dir->getRealPath());
            $progressBar->setProgress((int)$index);
        }

        $progressBar->finish();
    }

    /**
     * @param SplFileInfo[] $notfound
     */
    protected function removeNotFound(array $notfound, SymfonyStyle $io, bool $yes): void
    {
        $fs = new Filesystem();

        if (count($notfound) > 0) {
            $io->section('Found some branches that do not exist (anymore)');
            $delete = $yes || $io->confirm('Delete?', false);

            if ($delete) {
                foreach ($notfound as $dir) {
                    $fs->remove($dir->getRealPath());
                }
            }
        }
    }
}
