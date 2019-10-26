<?php

namespace duckpony\Console\Service;

use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class FilterSubFoldersService
{
    public function __invoke(Finder $directories, string $pattern, SymfonyStyle $io)
    {
        $formerCount = $directories->count();

        // Filter using pattern
        $directories->filter(static function (SplFileInfo $dir) use ($pattern, $io) {

            $matches = (bool)preg_match($pattern, $dir->getFilename());

            if (!$matches) {
                $io->writeln(
                    sprintf('%s not matching pattern %s', $dir->getFilename(), $pattern),
                    OutputInterface::VERBOSITY_DEBUG
                );
            }

            return $matches;
        });

        $io->writeln(
            sprintf('Filtered %d files not matching pattern %s', $formerCount - $directories->count(), $pattern),
            OutputInterface::VERBOSITY_DEBUG
        );
    }
}
