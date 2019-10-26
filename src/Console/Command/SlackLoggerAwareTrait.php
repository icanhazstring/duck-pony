<?php

namespace duckpony\Console\Command;

use Monolog\Handler\SlackHandler;
use Monolog\Logger;

trait SlackLoggerAwareTrait
{
    public function initSlackLogger(Logger $logger, array $config): void
    {
        $slackToken   = $config['Logger']['Slack']['Token'];
        $slackChannel = $config['Logger']['Slack']['Channel'];

        if ($slackToken && $slackChannel) {
            $slackHandler =
                new SlackHandler($slackToken,
                    $slackChannel,
                    'Duck-Pony',
                    true,
                    null,
                    Logger::CRITICAL,
                    true,
                    false,
                    true);
            $logger->pushHandler($slackHandler);
        }
    }
}
