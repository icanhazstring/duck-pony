<?php

declare(strict_types=1);

namespace duckpony\Console\Command\Option;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @uses \Zend\Config\Config
 */
trait PatternOptionTrait
{
    protected function configurePatternOption(): void
    {
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
    }

    protected function getPattern(InputInterface $input): string
    {
        return $input->getOption('pattern') ?? $this->config->pattern;
    }
}
