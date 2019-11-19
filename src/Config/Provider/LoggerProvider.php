<?php

declare(strict_types=1);

namespace duckpony\Config\Provider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Zend\Config\Config;

class LoggerProvider extends AbstractServiceProvider
{
    protected $provides = [
        LoggerInterface::class
    ];

    public function register(): void
    {
        /** @var Config $config */
        $config = $this->getContainer()->get(Config::class)->get(LoggerInterface::class);

        $logger = new Logger('logger');
        $logger->pushHandler(new StreamHandler(fopen('php://stdout', 'wb')));

        if (isset($config->slack, $config->slack->token, $config->slack->channel)) {
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

        $this->getLeagueContainer()->add(LoggerInterface::class, $logger);
    }
}
