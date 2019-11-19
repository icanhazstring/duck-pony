<?php

declare(strict_types=1);

use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

return (static function() {
    $container = new Container();
    $container->delegate((new ReflectionContainer())->cacheResolutions());
    $container->defaultToShared();

    $logger = new Monolog\Logger('logger');
    $logger->pushHandler(new StreamHandler(fopen('php://stdout', 'wb')));

    $container->add(LoggerInterface::class, $logger);

    return $container;
})();
