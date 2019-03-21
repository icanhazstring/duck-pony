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
use Symfony\Component\Yaml\Yaml;

class CleanBranchCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'Folder');
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');
        $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Confirm questions with yes');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Config',
            $this->getRootPath() . '/config/config.yml');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force delete');
        $this->addArgument('branchname-filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL , 'Filter branchname');

        $this->setName('folder:clean')
            ->setDescription('Scan folder and clean branches')
            ->setHelp(
                <<<EOT
Scan folder iterate over sub folders and removes
them under certain conditions
EOT
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $folder = $input->getArgument('folder');
        $folder = stream_resolve_include_path($folder);

        $statuses = $this->fetchStatuses($input->getOption('status'));
        $invert = $input->getOption('invert');
        $force = $input->getOption('force');
        $yes = $input->getOption('yes') ?: $force;
        $config = Yaml::parse(file_get_contents($input->getOption('config')));
        $pattern = $input->getOption('pattern') ?? $config['CleanBranch']['pattern'];
        $branchNameFilter = $input->getArgument('branchname-filter');

        $finder = new Finder();
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

        $fs = new Filesystem();
        $remove = [];
        $notfound = [];

        // If we don't force cleanup, filter out non matching branches
        if (!$force) {
            // Filter using pattern
            $directories->filter(function (\SplFileInfo $dir) use ($pattern, $io) {

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

            try {
                $issue = $issueService->get($branchName, ['fields' => ['status']]);

                if (!empty($statuses)) {
                    $issueStatus = strtolower($issue->fields->status->name);

                    if ($invert) {
                        if (!in_array($issueStatus, $statuses, true)) {
                            $remove[] = $dir;
                        }
                    } else {
                        if (in_array($issueStatus, $statuses, true)) {
                            $remove[] = $dir;
                        }
                    }
                }
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                $notfound[] = $dir;
            }

            $progressBar->setProgress((int) $index);
        }

        $progressBar->finish();

        $io->title('Remove matching branches');
        $progressBar = $io->createProgressBar(count($remove));

        foreach ($remove as $index => $dir) {
            $fs->remove($dir->getRealPath());
            $progressBar->setProgress((int) $index);
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

    /**
     * @param string $statuses
     * @return string[]
     */
    private function fetchStatuses(string $statuses): array
    {
        $splitStatuses = explode(',', $statuses);
        $splitStatuses = array_map('trim', $splitStatuses);

        return array_map('strtolower', $splitStatuses);
    }
}
