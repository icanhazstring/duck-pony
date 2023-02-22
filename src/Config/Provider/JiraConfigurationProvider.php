<?php

declare(strict_types=1);

namespace duckpony\Config\Provider;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Configuration\ConfigurationInterface;
use Laminas\Config\Config;
use League\Container\ServiceProvider\AbstractServiceProvider;

class JiraConfigurationProvider extends AbstractServiceProvider
{
    protected $provides = [
        ConfigurationInterface::class
    ];

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides);
    }

    public function register(): void
    {
        /** @var \Laminas\Config\Config $config */
        $config = $this->getContainer()->get(Config::class)->get(ConfigurationInterface::class);

        $this->getContainer()->add(
            ConfigurationInterface::class,
            new ArrayConfiguration($config->toArray())
        );
    }
}
