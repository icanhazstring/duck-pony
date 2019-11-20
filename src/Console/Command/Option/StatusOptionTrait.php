<?php

declare(strict_types=1);

namespace duckpony\Console\Command\Option;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\StyleInterface;

trait StatusOptionTrait
{
    protected function configureStatusOption(): void
    {
        $this->addOption('status', 's', InputOption::VALUE_REQUIRED, 'Status');
        $this->addOption('invert', 'i', InputOption::VALUE_NONE, 'Invert status');
    }

    protected function getStatusList(InputInterface $input, StyleInterface $output): array
    {
        $statusesString = $input->getOption('status');

        if (!$statusesString) {
            $output->error('You need to pass a list of statuses - see README.md');

            die(1);
        }

        $statuses = explode(',', $statusesString);
        $statuses = array_map('trim', $statuses);
        $statuses = array_map('strtolower', $statuses);

        return $statuses;
    }

    protected function getInvert(InputInterface $input): bool
    {
        return $input->getOption('invert');
    }
}
