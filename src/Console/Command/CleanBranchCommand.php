<?php

namespace duckpony\Console\Command;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CleanBranchCommand extends Command
{
    protected function configure()
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'Folder');
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');

        $this->setName('folder:clean')
            ->setDescription('Scan folder an clean branches')
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

        $status = $input->getOption('status');

        var_dump($status);

        $finder = new Finder();
        $files = $finder->files()->in($folder);

        $issueService = new IssueService(new ArrayConfiguration([
            'jiraHost' => 'https://icanhazstring.atlassian.net',
            'jiraUser' => 'blubb0r05+jira',
            'jiraPassword' => '4piUs3r',
        ]));

        $io->title('Scan folder ' . $folder . ' for outdated issues');
        $io->progressStart(count($files->directories()));

        $fs = new Filesystem();
        $remove = [];
        $notfound = [];

        foreach ($files->directories() as $dir) {
            /** @var SplFileInfo $dir */
            $branchName = $dir->getFilename();

            try {
                $issue = $issueService->get($branchName, ['fields' => ['status']]);

                if ($status) {
                    $issueStatus = strtolower($issue->fields->status->name);
                    $status = strtolower($status);

                    if ($status === $issueStatus) {
                        $remove[] = $dir;
                    }
                }
            } catch (\Exception $e) {
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
            $delete = $io->confirm('Delete?');

            if ($delete) {
                foreach ($notfound as $dir) {
                    $fs->remove($dir->getRealPath());
                }
            }
        }
    }

}
