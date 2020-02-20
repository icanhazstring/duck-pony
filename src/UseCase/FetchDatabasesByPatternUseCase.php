<?php

declare(strict_types=1);

namespace duckpony\UseCase;

use duckpony\Config\Provider\PDOConnectionProvider;
use PDO;

class FetchDatabasesByPatternUseCase
{
    /** @var PDOConnectionProvider */
    private $pdoConnectionProvider;

    public function __construct(PDOConnectionProvider $pdoConnectionProvider)
    {
        $this->pdoConnectionProvider = $pdoConnectionProvider;
    }

    /**
     * @return string[]
     */
    public function execute(string $pattern): array
    {
        $databases = $this->pdoConnectionProvider->getConnection()->query('SHOW DATABASES')->fetchAll();
        $databases = array_map(static function ($resultRow) {
            return $resultRow['Database'];
        }, $databases);

        return array_filter($databases, static function ($database) use ($pattern) {
            return preg_match($pattern, $database);
        });
    }
}
