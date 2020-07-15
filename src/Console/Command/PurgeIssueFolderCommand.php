<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use duckpony\Console\Command\Argument\BranchnameFilterArgumentTrait;
use duckpony\Console\Command\Argument\FolderArgumentTrait;
use duckpony\Console\Command\Option\KeepDaysOptionTrait;
use duckpony\Console\Command\Option\PatternOptionTrait;
use duckpony\Console\Command\Option\StatusOptionTrait;
use duckpony\Exception\JiraTicketNotFoundException;
use duckpony\Rule\IsDirectoryTooOld;
use duckpony\Rule\IsIssueWithGivenStatusRule;
use duckpony\Service\FilterSubFoldersService;
use duckpony\UseCase\FetchIssueUseCase;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Zend\Config\Config;

class PurgeIssueFolderCommand extends Command
{
    use AliasDeprecationTrait;

    use FolderArgumentTrait;
    use BranchnameFilterArgumentTrait;
    use PatternOptionTrait;
    use StatusOptionTrait;
    use KeepDaysOptionTrait;

    protected const DEPRECATION_ALIAS = 'folder:clean';

    /** @var FilterSubFoldersService */
    private $filterSubFoldersService;
    /** @var LoggerInterface */
    private $logger;
    /** @var Config */
    private $config;
    /** @var FetchIssueUseCase */
    private $fetchIssueUseCase;
    /** @var IsIssueWithGivenStatusRule */
    private $isIssueWithGivenStatusRule;
    /** @var IsDirectoryTooOld */
    private $isDirectoryTooOld;

    public function __construct(
        Config $config,
        FilterSubFoldersService $filterSubFoldersService,
        FetchIssueUseCase $fetchIssueUseCase,
        IsIssueWithGivenStatusRule $isIssueWithGivenStatusRule,
        IsDirectoryTooOld $isDirectoryTooOld
    ) {
        parent::__construct('issue:purge-folder');

        // Used in traits
        $this->config = $config->get(self::class);
        $this->filterSubFoldersService = $filterSubFoldersService;
        $this->fetchIssueUseCase = $fetchIssueUseCase;
        $this->isIssueWithGivenStatusRule = $isIssueWithGivenStatusRule;
        $this->isDirectoryTooOld = $isDirectoryTooOld;
    }

    protected function configure(): void
    {
        $this->configureFolderArgument();
        $this->configureStatusOption();
        $this->configurePatternOption();
        $this->configureKeepDaysOption();
        $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Confirm questions with yes');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force delete');
        $this->configureBranchnameFilterArgument();

        $this->setAliases([self::DEPRECATION_ALIAS])
            ->setDescription('Scan folder and clean branches')
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
        $this->checkAliasDeprecation($input, $io);

        $statuses = $this->getStatusList($input, $io);
        $folder = $this->getFolder($input);
        $invert = $this->getInvert($input);
        $force = $input->getOption('force');
        $yes = $input->getOption('yes') ?: $force;
        $pattern = $this->getPattern($input);
        $branchNameFilter = $this->getBranchnameFilter($input);

        try {
            $keepDays = $this->getKeepDays($input);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return 1;
        }

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
            $statuses,
            $invert,
            $keepDays
        );

        $this->removeMatchingBranches($remove, $io);

        $this->removeNotFound($notfound, $io, $yes);

        return 0;
    }

    /**
     * @param SymfonyStyle $io
     * @param Finder       $directories
     * @param array        $branchNameFilter
     * @param array        $statuses
     * @param bool         $invert
     * @param int|null     $keepDays
     * @return array
     */
    protected function findBranchesToCleanup(
        SymfonyStyle $io,
        Finder $directories,
        array $branchNameFilter,
        array $statuses,
        bool $invert,
        ?int $keepDays
    ): array {
        $progressBar = $io->createProgressBar(count($directories->directories()));

        $remove = [];
        $notfound = [];

        /* @var SplFileInfo $dir */
        foreach ($directories->directories() as $index => $dir) {
            $issue = null;
            try {
                $issue = $this->fetchIssueUseCase->execute($dir->getFilename(), $branchNameFilter);
            } catch (JiraTicketNotFoundException $e) {
                $notfound[] = $dir;
            }

            $branchIsTooOld = false;
            if ($keepDays !== null) {
                $branchIsTooOld = $this->isDirectoryTooOld->appliesTo($dir, $keepDays);
            }

            if ($branchIsTooOld || $this->isIssueWithGivenStatusRule->appliesTo($issue, $statuses, $invert)) {
                $remove[] = $dir;
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
