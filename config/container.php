<?php

declare(strict_types=1);

use duckpony\Config\Provider\JiraConfigurationProvider;
use duckpony\Config\Provider\LoggerProvider;
use duckpony\Config\Provider\PDOConnectionProvider;
use duckpony\Config\Provider\PDOProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Laminas\Config\Config;

return (static function () {
    $container = new Container();
    $container->delegate((new ReflectionContainer(true)));
    $container->defaultToShared();

    $container->add(Config::class, require __DIR__ . '/config.php');
    $container->addServiceProvider(new LoggerProvider());
    $container->addServiceProvider(new PDOProvider());
    $container->add(PDOConnectionProvider::class, function () use ($container) {
        $pdoConfig = $container->get(Config::class)->get(PDO::class);
        return new PDOConnectionProvider(
            $pdoConfig->hostname,
            $pdoConfig->username,
            $pdoConfig->password,
        );

    });

    $container->addServiceProvider(new JiraConfigurationProvider());

    return $container;
})();
