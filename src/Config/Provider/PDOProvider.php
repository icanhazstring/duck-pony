<?php

declare(strict_types=1);

namespace duckpony\Config\Provider;

use League\Container\ServiceProvider\AbstractServiceProvider;
use PDO;
use Laminas\Config\Config;

class PDOProvider extends AbstractServiceProvider
{
    protected array $provides = [
        PDOConnectionProvider::class,
    ];

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides);
    }

    public function register(): void
    {
        $container = $this->getContainer();
        /** @var Config $config */
        $config = $container->get(Config::class)->get(PDO::class);

        $pdoConnectionProvider = new PDOConnectionProvider(
            $config->get('hostname'),
            $config->get('username'),
            $config->get('password')
        );
        $container->add(PDOConnectionProvider::class, $pdoConnectionProvider);
    }
}
