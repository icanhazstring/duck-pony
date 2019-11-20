<?php

declare(strict_types=1);

namespace duckpony\Console\Command\Argument;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

trait BranchnameFilterArgumentTrait
{
    protected function configureBranchnameFilterArgument(): void
    {
        $this->addArgument(
            'branchname-filter',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Remove given values from branch names'
        );
    }

    /**
     * @return string[]
     */
    protected function getBranchnameFilter(InputInterface $input): array
    {
        return $input->getArgument('branchname-filter') ?? [];
    }
}
