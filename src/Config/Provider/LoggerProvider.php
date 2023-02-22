<?php

declare(strict_types=1);

namespace duckpony\Config\Provider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Laminas\Config\Config;

class LoggerProvider extends AbstractServiceProvider
{
    protected array $provides = [
        LoggerInterface::class,
    ];

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides);
    }

    public function register(): void
    {
        $container = $this->getContainer();
        /** @var Config $config */
        $config = $container->get(Config::class)->get(LoggerInterface::class);

        $logger = new Logger('logger');
        $logger->pushHandler(new StreamHandler(fopen('php://stdout', 'wb')));

        if (isset($config->slack, $config->slack->token, $config->slack->channel, $config->slack->level)) {
            $handler = new SlackHandler(
                $config->slack->token,
                $config->slack->channel,
                'Duck-Pony',
                true,
                null,
                $config->slack->level,
                true,
                false,
                true
            );

            $logger->pushHandler($handler);
        }

        $container->add(LoggerInterface::class, $logger);
    }
}
