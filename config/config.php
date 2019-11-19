<?php

declare(strict_types=1);

use Zend\Config\Config;
use Zend\Config\Factory;

return new Config(Factory::fromFiles(glob(__DIR__ . '/autoload/*.config.php')));
