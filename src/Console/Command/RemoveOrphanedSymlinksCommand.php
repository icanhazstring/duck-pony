<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use duckpony\Console\Command\Argument\FolderArgumentTrait;
use FilesystemIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RemoveOrphanedSymlinksCommand extends Command
{
    use FolderArgumentTrait;

    /**
     * Configures the CLI Command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->configureFolderArgument();

        $this->setName('symlinks:remove_orphaned')
            ->setDescription('Removes orphaned symlinks of a given folder')
            ->setHelp('Removes only orphaned symlinks under a given folder without any recursion.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $folder = $this->getFolder($input);

        $directoryIterator = new FilesystemIterator($folder, FilesystemIterator::SKIP_DOTS);
        $brokenSymlinks = [];
        foreach ($directoryIterator as $item) {
            $symlink = $item->getPathName();
            if (is_link($symlink) && !file_exists($symlink)) {
                $brokenSymlinks[] = $symlink;
            }
        }

        $io->progressStart(count($brokenSymlinks));

        foreach ($brokenSymlinks as $brokenSymlink) {
            unlink($brokenSymlink);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $io->progressAdvance();
        }

        $io->progressFinish();

        return 0;
    }
}
