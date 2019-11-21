<?php

declare(strict_types=1);

namespace duckpony\Console\Command\Option;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait DryRunOptionTrait
{
    protected function configureDryRunOption(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Enable dry run');
    }

    protected function isDryRun(InputInterface $input): bool
    {
        return $input->getOption('dry-run');
    }
}
