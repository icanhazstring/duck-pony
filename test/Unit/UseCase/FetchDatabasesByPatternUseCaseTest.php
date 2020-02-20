<?php

declare(strict_types=1);

namespace duckpony\Test\Unit\UseCase;

use duckpony\Config\Provider\PDOConnectionProvider;
use duckpony\UseCase\FetchDatabasesByPatternUseCase;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class FetchDatabasesByPatternUseCaseTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldReturnMatchingDatabases(): void
    {
        $statement = $this->prophesize(PDOStatement::class);
        $statement->fetchAll()->willReturn([
            ['Database' => 'testA'],
            ['Database' => 'testB'],
            ['Database' => 'fubar']
        ]);

        $pdo = $this->prophesize(PDO::class);
        $pdo->query('SHOW DATABASES')->willReturn($statement->reveal());

        $pdoConnectionProvider = $this->prophesize(PDOConnectionProvider::class);
        $pdoConnectionProvider->getConnection()->willReturn($pdo->reveal());

        $useCase = new FetchDatabasesByPatternUseCase($pdoConnectionProvider->reveal());
        $result = $useCase->execute('/test/');

        $this->assertCount(2, $result);
    }

    /**
     * @test
     */
    public function itShouldReturnEmptyListWhenPatternDoesNotMatch(): void
    {
        $statement = $this->prophesize(PDOStatement::class);
        $statement->fetchAll()->willReturn([
            ['Database' => 'fubarA'],
            ['Database' => 'fubarB']
        ]);

        $pdo = $this->prophesize(PDO::class);
        $pdo->query('SHOW DATABASES')->willReturn($statement->reveal());

        $pdoConnectionProvider = $this->prophesize(PDOConnectionProvider::class);
        $pdoConnectionProvider->getConnection()->willReturn($pdo->reveal());

        $useCase = new FetchDatabasesByPatternUseCase($pdoConnectionProvider->reveal());
        $result = $useCase->execute('/test/');

        $this->assertEmpty($result);
    }
}
