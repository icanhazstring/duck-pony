<?php

declare(strict_types=1);

namespace duckpony\UseCase;

use PDO;

class FetchDatabasesByPatternUseCase
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return string[]
     */
    public function execute(string $pattern): array
    {
        $databases = $this->pdo->query('SHOW DATABASES')->fetchAll();

        return array_filter($databases, static function ($database) use ($pattern) {
            return preg_match($pattern, $database);
        });
    }
}
