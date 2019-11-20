<?php

declare(strict_types=1);

namespace duckpony\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\StyleInterface;

trait AliasDeprecationTrait
{
    protected function checkAliasDeprecation(InputInterface $input, StyleInterface $io): void
    {
        if ($input->getArgument('command') === self::DEPRECATION_ALIAS) {
            $io->warning(
                sprintf('Usage of %s is deprecated, please use %s', self::DEPRECATION_ALIAS, $this->getName())
            );
        }
    }
}
