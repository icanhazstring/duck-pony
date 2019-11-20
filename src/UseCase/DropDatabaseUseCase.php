<?php

declare(strict_types=1);

namespace duckpony\UseCase;

use PDO;
use Psr\Log\LoggerInterface;
use Throwable;

class DropDatabaseUseCase
{
    private const DATABASE_BLACKLIST = [
        'information_schema',
        'perfomance_schema',
        'mysql',
    ];

    /** @var PDO */
    private $pdo;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function execute(string $databaseName): void
    {
        if (in_array($databaseName, self::DATABASE_BLACKLIST, true)) {
            return;
        }

        try {
            $result = $this->pdo->prepare(
                sprintf('DROP DATABASE IF EXISTS `%s`', $databaseName)
            )->execute();

            if (!$result) {
                $this->logger->critical(
                    'Dropping the database failed',
                    [
                        'CleanMySQLDatabaseName' => 'Error deleting a database!',
                        'Unexpected database'    => $databaseName,
                        'PDO ErrorInfo'          => $this->pdo->errorInfo()
                    ]
                );
            }
        } catch (Throwable $e) {
            $this->logger->critical(
                'Dropping the database failed',
                [
                    'CleanMySQLDatabaseName' => 'Error deleting a database!',
                    'Unexpected database'    => $databaseName,
                    'Errormessage'           => $e->getMessage(),
                    'StackTrace'             => $e->getTraceAsString()
                ]
            );
        }
    }
}
