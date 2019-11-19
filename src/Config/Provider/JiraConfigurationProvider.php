<?php

declare(strict_types=1);

namespace duckpony\Config\Provider;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Configuration\ConfigurationInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Zend\Config\Config;

class JiraConfigurationProvider extends AbstractServiceProvider
{
    protected $provides = [
        ConfigurationInterface::class
    ];

    public function register(): void
    {
        /** @var Config $config */
        $config = $this->getLeagueContainer()->get(Config::class)->get(ConfigurationInterface::class);

        $this->getLeagueContainer()->add(ConfigurationInterface::class, new ArrayConfiguration($config->toArray()));
    }
}
