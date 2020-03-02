<?php

namespace duckpony\Rule;

use JiraRestApi\Issue\Issue;

class IsIssueWithGivenStatusRule
{

    /**
     * This Rule returns true if the given $issue has a status that is present in $statuses.
     * If $invert is set to true, then this rules returns true when the issue's status is
     * NOT present in the $statuses array.
     */
    public function appliesTo(?Issue $issue, array $statuses, bool $invert): bool
    {
        if (!empty($statuses) && $issue) {
            $issueStatus = strtolower($issue->fields->status->name);

            $statusFound = in_array($issueStatus, $statuses, true);
            if ($invert) {
                if (!$statusFound) {
                    return true;
                }
            } elseif ($statusFound) {
                return true;
            }
        }
        return false;
    }
}
