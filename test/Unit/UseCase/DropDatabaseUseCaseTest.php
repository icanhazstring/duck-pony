<?php

declare(strict_types=1);

namespace duckpony\Test\Unit\UseCase;

use duckpony\Config\Provider\PDOConnectionProvider;
use duckpony\UseCase\DropDatabaseUseCase;
use Exception;
use PDO;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Exception\Call\UnexpectedCallException;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

class DropDatabaseUseCaseTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function itShouldDoNothingWhenAttemptToDropBlacklistedDatabase(): void
    {
        $pdoStatement = $this->prophesize(\PDOStatement::class);
        $pdoStatement->execute()->willReturn(false);

        $pdo = $this->prophesize(PDO::class);
        $pdo->prepare(Argument::any())->willReturn($pdoStatement);

        $pdoConnectionProvider = $this->prophesize(PDOConnectionProvider::class);
        $pdoConnectionProvider->getConnection()->willReturn($pdo->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->critical(Argument::any())->shouldNotBeCalled();

        $useCase = new DropDatabaseUseCase($pdoConnectionProvider->reveal(), $logger->reveal());
        $useCase->execute('mysql');
    }

    /**
     * @test
     */
    public function itShouldCallToPdoOnValidDatabase(): void
    {
        $pdo = $this->prophesize(PDO::class);
        $pdo->prepare(Argument::containingString('test'))
            ->shouldBeCalled()
            ->willReturn(true);

        $pdoConnectionProvider = $this->prophesize(PDOConnectionProvider::class);
        $pdoConnectionProvider->getConnection()->willReturn($pdo->reveal());

        $logger = $this->prophesize(LoggerInterface::class);

        $useCase = new DropDatabaseUseCase($pdoConnectionProvider->reveal(), $logger->reveal());
        $useCase->execute('test');
    }

    /**
     * @test
     */
    public function itShouldCallLoggerOnFalsePdoResult(): void
    {
        $pdo = $this->prophesize(PDO::class);
        $pdo->prepare(Argument::containingString('test'))
            ->shouldBeCalled()
            ->willReturn(false);

        $pdoConnectionProvider = $this->prophesize(PDOConnectionProvider::class);
        $pdoConnectionProvider->getConnection()->willReturn($pdo->reveal());


        $logger = $this->prophesize(LoggerInterface::class);
        $logger->critical(Argument::cetera())->shouldBeCalled();

        $useCase = new DropDatabaseUseCase($pdoConnectionProvider->reveal(), $logger->reveal());
        $useCase->execute('test');
    }

    /**
     * @test
     */
    public function itShouldCallLoggerOnException(): void
    {
        $pdo = $this->prophesize(PDO::class);
        $pdo->prepare(Argument::containingString('test'))
            ->willThrow(new Exception());

        $pdoConnectionProvider = $this->prophesize(PDOConnectionProvider::class);
        $pdoConnectionProvider->getConnection()->willReturn($pdo->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->critical(Argument::cetera())->shouldBeCalled();

        $useCase = new DropDatabaseUseCase($pdoConnectionProvider->reveal(), $logger->reveal());
        $useCase->execute('test');
    }
}
