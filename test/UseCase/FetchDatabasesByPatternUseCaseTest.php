<?php

declare(strict_types=1);

namespace duckpony\Test\UseCase;

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
        $statement->fetchAll()->willReturn(['testA', 'testB', 'fubar']);

        $pdo = $this->prophesize(PDO::class);
        $pdo->query('SHOW DATABASES')->willReturn($statement->reveal());

        $useCase = new FetchDatabasesByPatternUseCase($pdo->reveal());
        $result = $useCase->execute('/test/');

        $this->assertCount(2, $result);
    }

    /**
     * @test
     */
    public function itShouldReturnEmptyListWhenPatternDoesNotMatch(): void
    {
        $statement = $this->prophesize(PDOStatement::class);
        $statement->fetchAll()->willReturn(['fubarA', 'fubarB']);

        $pdo = $this->prophesize(PDO::class);
        $pdo->query('SHOW DATABASES')->willReturn($statement->reveal());

        $useCase = new FetchDatabasesByPatternUseCase($pdo->reveal());
        $result = $useCase->execute('/test/');

        $this->assertEmpty($result);
    }
}
