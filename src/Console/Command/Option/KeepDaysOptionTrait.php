<?php

declare(strict_types=1);

namespace duckpony\Console\Command\Option;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait KeepDaysOptionTrait
{
    protected function configureKeepDaysOption(): void
    {
        $this->addOption(
            'keep-days',
            null,
            InputOption::VALUE_OPTIONAL,
            'The number of days a branch is allowed to remain.'
        );
    }

    protected function getKeepDays(InputInterface $input): ?int
    {
        $keepDaysString = $input->getOption('keep-days');
        if ($keepDaysString === null) {
            return null;
        }

        if (!is_numeric($keepDaysString) || $keepDaysString <= 0) {
            throw new InvalidArgumentException(
                sprintf('Option "keep-days" must be an integer and bigger than 0, "%s" given.', $keepDaysString)
            );
        }

        return (int) $keepDaysString;
    }
}
