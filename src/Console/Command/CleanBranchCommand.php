<?php
declare(strict_types=1);

namespace duckpony\Console\Command;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
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
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Config', $this->getRootPath() . '/config/config.yml');
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

    protected function execute(InputInterface $input, OutputInterface $output)
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
        $files = $finder->files()->in($folder);

        $issueService = new IssueService(new ArrayConfiguration([
            'jiraHost' => $config['CleanBranch']['hostname'],
            'jiraUser' => $config['CleanBranch']['username'],
            'jiraPassword' => $config['CleanBranch']['password']
        ]));

        $io->title('Scan Folder ' . $folder . ' for outdated issues');

        $fs = new Filesystem();
        $remove = [];
        $notfound = [];

        // If we don't force cleanup, filter out non matching branches
        if (!$force) {
            // Filter using pattern
            $files->filter(function(\SplFileInfo $dir) use ($pattern) {
                return (bool)preg_match($pattern, $dir->getFilename());
            });
        }

        $io->progressStart(count($files->directories()));

        foreach ($files->directories() as $dir) {

            $branchName = !empty($branchNameFilter)
                ? str_replace($branchNameFilter, '', $dir->getFilename())
                : $dir->getFilename();

            try {
                $issue = $issueService->get($branchName, ['fields' => ['status']]);

                if (!empty($statuses)) {
                    $issueStatus = strtolower($issue->fields->status->name);

                    if ($invert) {
                        if (!in_array($issueStatus, $statuses)) {
                            $remove[] = $dir;
                        }
                    } else {
                        if (in_array($issueStatus, $statuses)) {
                            $remove[] = $dir;
                        }
                    }
                }
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                $notfound[] = $dir;
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->title('Remove matching branches');
        $io->progressStart(count($remove));

        foreach ($remove as $dir) {
            $fs->remove($dir->getRealPath());
            $io->progressAdvance();
        }

        $io->progressFinish();

        if (count($notfound) > 0) {
            $io->section('Found some non matching branches');
            $delete = $yes || $io->confirm('Delete?', false);

            if ($delete) {
                foreach ($notfound as $dir) {
                    $fs->remove($dir->getRealPath());
                }
            }
        }
    }

    private function fetchStatuses($statuses)
    {
        $statuses = explode(',', $statuses);
        $statuses = array_map('trim', $statuses);
        return array_map('strtolower', $statuses);
    }
}
