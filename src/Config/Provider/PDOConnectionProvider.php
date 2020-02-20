<?php

namespace duckpony\Config\Provider;

use PDO;

class PDOConnectionProvider
{
    private $hostname;
    private $username;
    private $password;

    public function __construct(string $hostname, string $username, string $password)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
    }

    public function getConnection()
    {
        $connectionString = sprintf('mysql:host=%s;', $this->hostname);
        return new PDO($connectionString, $this->username, $this->password);
    }
}
