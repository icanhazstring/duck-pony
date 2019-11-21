<?php

declare(strict_types=1);

namespace duckpony\Config\Provider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use PDO;
use Zend\Config\Config;

class PDOProvider extends AbstractServiceProvider
{
    protected $provides = [
        PDO::class
    ];

    public function register(): void
    {
        /** @var Config $config */
        $config = $this->getLeagueContainer()->get(Config::class)->get(PDO::class);

        $connectionString = sprintf('mysql:host=%s;', $config->hostname);
        $pdo = new PDO($connectionString, $config->username, $config->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->getLeagueContainer()->add(PDO::class, $pdo);
    }
}
