<?php

declare(strict_types=1);

namespace duckpony\UseCase;

use duckpony\Config\Provider\PDOConnectionProvider;
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

    /** @var PDOConnectionProvider */
    private $pdoConnectionProvider;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(PDOConnectionProvider $pdoConnectionProvider, LoggerInterface $logger)
    {
        $this->pdoConnectionProvider = $pdoConnectionProvider;
        $this->logger = $logger;
    }

    public function execute(string $databaseName): void
    {
        if (in_array($databaseName, self::DATABASE_BLACKLIST, true)) {
            return;
        }

        try {
            $result = $this->pdoConnectionProvider->getConnection()->prepare(
                sprintf('DROP DATABASE IF EXISTS `%s`', $databaseName)
            )->execute();

            if (!$result) {
                $this->logger->critical(
                    'Dropping the database failed',
                    [
                        'CleanMySQLDatabaseName' => 'Error deleting a database!',
                        'Unexpected database'    => $databaseName,
                        'PDO ErrorInfo'          => $this->pdoConnectionProvider->getConnection()->errorInfo()
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
