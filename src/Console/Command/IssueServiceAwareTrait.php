<?php

namespace duckpony\Console\Command;

use Exception;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use Symfony\Component\Console\Style\SymfonyStyle;

trait IssueServiceAwareTrait
{
    public function initIssueService(array $config, SymfonyStyle $io): IssueService
    {
        try {
            return new IssueService(new ArrayConfiguration([
                'jiraHost'     => $config['Jira']['hostname'],
                'jiraUser'     => $config['Jira']['username'],
                'jiraPassword' => $config['Jira']['password']
            ]));
        } catch (JiraException | Exception $e) {
            $io->error($e->getMessage());

            die(1);
        }
    }
}
