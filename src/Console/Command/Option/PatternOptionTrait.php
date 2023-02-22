<?php

declare(strict_types=1);

namespace duckpony\Console\Command\Option;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @uses \Laminas\Config\Config
 */
trait PatternOptionTrait
{
    protected function configurePatternOption(): void
    {
        $this->addOption('pattern', 'p', InputOption::VALUE_REQUIRED, 'Branch pattern');
    }

    protected function getPattern(InputInterface $input): string
    {
        $configPattern = property_exists($this, 'config') ? $this->config->get('pattern') : '';

        return $input->getOption('pattern') ?? $configPattern;
    }
}
