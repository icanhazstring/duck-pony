<?php

declare(strict_types=1);

namespace duckpony\Config\Provider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use PDO;
use Zend\Config\Config;

class PDOProvider extends AbstractServiceProvider
{
    protected $provides = [
        PDOConnectionProvider::class
    ];

    public function register(): void
    {
        /** @var Config $config */
        $config = $this->getLeagueContainer()->get(Config::class)->get(PDO::class);

        $pdoConnectionProvider = new PDOConnectionProvider($config->hostname, $config->username, $config->password);

        $this->getLeagueContainer()->add(PDOConnectionProvider::class, $pdoConnectionProvider);
    }
}
