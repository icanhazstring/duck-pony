<?php

namespace duckpony\Test\Unit\Rule;

use duckpony\Exception\JiraTicketNotFoundException;
use duckpony\Rule\IsIssueWithGivenStatusRule;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueField;
use PHPUnit\Framework\TestCase;

class IsIssueWithGivenStatusRuleTest extends TestCase
{
    public function issuesDataProvider(): array
    {

        $issue = new Issue();
        $issue->fields->status->name = 'test1';
        return [
            'it should return true given an issue with status included in statuses and not inverted' => [
                'given' => [
                    'issue' => $issue,
                    'statuses' => [
                        'test1',
                        'test2',
                    ],
                    'invert' => false
                ],
                'expected' => true
            ],
            'it should return false given an issue with status included in statuses and inverted' => [
                'given' => [
                    'issue' => $issue,
                    'statuses' => [
                        'test1',
                        'test2',
                    ],
                    'invert' => true
                ],
                'expected' => false
            ],
            'it should return false given an issue with status not included in statuses and not inverted' => [
                'given' => [
                    'issue' => $issue,
                    'statuses' => [
                        'test2',
                        'test3',
                    ],
                    'invert' => false
                ],
                'expected' => false
            ],
            'it should return true given an issue with status not included in statuses and inverted' => [
                'given' => [
                    'issue' => $issue,
                    'statuses' => [
                        'test2',
                        'test3',
                    ],
                    'invert' => true
                ],
                'expected' => true
            ],
            'it should return false given when no statuses are passed' => [
                'given' => [
                    'issue' => $issue,
                    'statuses' => [],
                    'invert' => false
                ],
                'expected' => false
            ],
            'it should return false given null is passed as issue' => [
                'given' => [
                    'issue' => null,
                    'statuses' => [],
                    'invert' => false
                ],
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider issuesDataProvider
     * @test
     */
    public function itShouldReturnCorrectlyAppliedRuleResult(array $given, bool $expected): void
    {
        $rule = new isIssueWithGivenStatusRule();
        $actual = $rule->appliesTo($given['issue'], $given['statuses'], $given['invert']);
        $this->assertEquals($expected, $actual);
    }
}
