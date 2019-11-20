<?php

declare(strict_types=1);

namespace duckpony\UseCase;

use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use Psr\Log\LoggerInterface;
use Throwable;

class FetchIssueUseCase
{
    /** @var IssueService */
    private $issueService;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(IssueService $issueService, LoggerInterface $logger)
    {
        $this->issueService = $issueService;
        $this->logger = $logger;
    }

    public function execute(string $issueName, array $issueNameFilter = []): ?Issue
    {
        $issueName = str_replace($issueNameFilter, '', $issueName);
        $issue = null;

        try {
            /** @var Issue $issue */
            $issue = $this->issueService->get($issueName, ['fields' => ['status']]);
        } catch (JiraException $e) {
            $this->logger->critical(
                'I encountered a problem fetching issue data from Jira',
                [
                    'Issue'          => $issueName,
                    'Possible cause' => 'Jira ticket was deleted'
                ]
            );
        } catch (Throwable $e) {
            $this->logger->critical(
                'I encountered an unknown problem fetching issue data from Jira',
                [
                    'Issue'          => $issueName,
                    'Possible cause' => 'Jira no reachable'
                ]
            );
        }

        return $issue;
    }
}
