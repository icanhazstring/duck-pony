<?php

declare(strict_types=1);

namespace duckpony\Test\Unit\UseCase;

use duckpony\UseCase\FetchIssueUseCase;
use Exception;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

class FetchIssueUseCaseTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function itShouldCallIssueServiceWithGivenIssueName(): void
    {
        $issueService = $this->prophesize(IssueService::class);
        $issueService->get('test', Argument::any())->shouldBeCalled();

        $logger = $this->prophesize(LoggerInterface::class);

        $useCase = new FetchIssueUseCase($issueService->reveal(), $logger->reveal());

        $useCase->execute('test');
    }

    /**
     * @test
     */
    public function itShouldCallIssueServiceWithFilteredIssueName(): void
    {
        $issueService = $this->prophesize(IssueService::class);
        $issueService->get('s', Argument::any())->shouldBeCalled();

        $logger = $this->prophesize(LoggerInterface::class);

        $useCase = new FetchIssueUseCase($issueService->reveal(), $logger->reveal());

        $useCase->execute('test', ['t', 'e']);
    }

    /**
     * @test
     */
    public function itShouldCallLoggerOnKnownJiraException(): void
    {
        $issueService = $this->prophesize(IssueService::class);
        $issueService->get(Argument::cetera())->willThrow(new JiraException(""));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->critical(
            Argument::containingString('problem'),
            Argument::containing('test')
        )->shouldBeCalled();

        $useCase = new FetchIssueUseCase($issueService->reveal(), $logger->reveal());

        $useCase->execute('test');
    }

    /**
     * @test
     */
    public function itShouldCallLoggerOnUnknownKnownJiraException(): void
    {
        $issueService = $this->prophesize(IssueService::class);
        $issueService->get(Argument::cetera())->willThrow(new Exception());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->critical(
            Argument::containingString('unknown problem'),
            Argument::containing('test')
        )->shouldBeCalled();

        $useCase = new FetchIssueUseCase($issueService->reveal(), $logger->reveal());

        $useCase->execute('test');
    }
}
