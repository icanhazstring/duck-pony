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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use SystemCtl\CommandFailedException;
use SystemCtl\Service;

class PurgeServiceCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'Folder');
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Config', $this->getRootPath() . '/config/config.yml');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force delete');
        $this->addOption('name', 'sn', InputOption::VALUE_REQUIRED, 'Name of service');

        $this->setName('folder:purge-service')
            ->setDescription('Scan folder an purge services with same name')
            ->setHelp(
                <<<EOT
Scan folder iterate over sub folders and removes
try to remove services by same name
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $folder = $input->getArgument('folder');
        $folder = stream_resolve_include_path($folder);

        $status = $input->getOption('status');
        $invert = $input->getOption('invert');
        $force = $input->getOption('force');
        $config = Yaml::parse(file_get_contents($input->getOption('config')));
        $name = $input->getOption('name');
        $pattern = $input->getOption('pattern') ?? $config['pattern'];

        $finder = new Finder();
        $files = $finder->files()->in($folder);

        $issueService = new IssueService(new ArrayConfiguration([
            'jiraHost' => $config['hostname'],
            'jiraUser' => $config['username'],
            'jiraPassword' => $config['password']
        ]));

        $io->title('Scan folder ' . $folder . ' for outdated issues');

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
            /** @var SplFileInfo $dir */
            $branchName = $dir->getFilename();

            try {
                $issue = $issueService->get($branchName, ['fields' => ['status']]);

                if ($status) {
                    $issueStatus = strtolower($issue->fields->status->name);
                    $status = strtolower($status);

                    if ($invert) {
                        if ($status !== $issueStatus) {
                            $remove[] = $dir;
                        }
                    } else {
                        if ($status === $issueStatus) {
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

            try {
                $service = new Service($name . $dir->getFilename());
                $service->stop();

                $io->progressAdvance();
            } catch (CommandFailedException $e) {
                var_dump($e);
            }

        }

        $io->progressFinish();
    }
}