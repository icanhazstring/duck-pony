<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait StatusesAwareCommandTrait
{
    /**
     * @return string[]
     */
    public function initStatuses(InputInterface $input, SymfonyStyle $io): array
    {
        $statusesString = $input->getOption('status');
        if (!$statusesString) {
            $io->error('You need to pass a list of statuses - see README.md');

            die(1);
        }

        $statuses = explode(',', $statusesString);
        $statuses = array_map('trim', $statuses);
        $statuses = array_map('strtolower', $statuses);

        return $statuses;
    }
}
