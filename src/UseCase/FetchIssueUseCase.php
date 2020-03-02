<?php

declare(strict_types=1);

namespace duckpony\UseCase;

use duckpony\Exception\JiraTicketNotFoundException;
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

    /**
     * @throws JiraTicketNotFoundException
     */
    public function execute(string $issueName, array $issueNameFilter = []): ?Issue
    {
        $issueName = str_replace($issueNameFilter, '', $issueName);
        $issue = null;

        try {
            /** @var Issue $issue */
            $issue = $this->issueService->get($issueName, ['fields' => ['status']]);
        } catch (JiraException $e) {
            if ($e->getCode() === 404) {
                throw new JiraTicketNotFoundException(
                    'Jira ticket does not exist or was deleted',
                    $e->getCode(),
                    $e
                );
            }
            $this->logger->critical(
                'I encountered a problem fetching issue data from Jira',
                [
                    'Issue'          => $issueName,
                    'Possible cause' => 'Unexpected Jira response'
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
