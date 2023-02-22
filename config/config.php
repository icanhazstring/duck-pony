<?php

declare(strict_types=1);

use Laminas\Config\Config;
use Laminas\Config\Factory;

return new Config(Factory::fromFiles(glob(__DIR__ . '/autoload/*.config.php')));
