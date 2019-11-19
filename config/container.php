<?php

declare(strict_types=1);

use duckpony\Config\Provider\JiraConfigurationProvider;
use duckpony\Config\Provider\LoggerProvider;
use duckpony\Config\Provider\PDOProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Zend\Config\Config;

return (static function () {
    $container = new Container();
    $container->delegate((new ReflectionContainer())->cacheResolutions());
    $container->defaultToShared();

    $container->add(Config::class, require __DIR__ . '/config.php');
    $container->addServiceProvider(LoggerProvider::class);
    $container->addServiceProvider(PDOProvider::class);
    $container->addServiceProvider(JiraConfigurationProvider::class);

    return $container;
})();
