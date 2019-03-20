<?php
declare(strict_types=1);

namespace duckpony\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class RemoveOrphanedSymlinksCommand
 *
 * @package duckpony\Console\Command
 * @author  Michel Petiton <michel@petiton.de>
 */
class RemoveOrphanedSymlinksCommand extends AbstractCommand
{
    /**
     * Configures the CLI Command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('folder', InputArgument::REQUIRED, 'Folder');

        $this->setName('symlinks:remove_orphaned')
            ->setDescription('Removes orphaned symlinks of a given folder')
            ->setHelp('Removes only orphaned symlinks under a given folder without any recursion.');
    }

    /**
     * Iterates a given directory and removes orphaned symlinks
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        $folder = $input->getArgument('folder');
        $folder = stream_resolve_include_path($folder);

        $directoryIterator = new \FilesystemIterator($folder, \FilesystemIterator::SKIP_DOTS);
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
            $io->progressAdvance();
        }

        $io->progressFinish();
    }
}
