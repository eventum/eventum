<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum\Export\Serializer;

use Eventum\Model\Entity\Issue;
use Port\ValueConverter\DateTimeToStringValueConverter;

class IssueSerializer
{
    /** @var DateTimeToStringValueConverter */
    private $dateTimeConverter;

    public function __construct()
    {
        $this->dateTimeConverter = new DateTimeToStringValueConverter();
    }

    public function __invoke(Issue $issue): array
    {
        $dateTimeConverter = $this->dateTimeConverter;

        return [
            'title' => $issue->getSummary(),
            'author_id' => $this->getAuthorId($issue),
            'project_id' => 1,
            'created_at' => $dateTimeConverter($issue->getCreatedDate()),
            'updated_at' => $dateTimeConverter($issue->getUpdatedDate()),
            'description' => $issue->getDescription(),
            'iid' => $issue->getId(),
            'updated_by_id' => null,
            'confidential' => false,
            'due_date' => null,
            'lock_version' => 0,
            'time_estimate' => 0,
            'relative_position' => 1073745862,
            'last_edited_at' => null,
            'last_edited_by_id' => null,
            'discussion_locked' => null,
            'closed_at' => null,
            'closed_by_id' => null,
            'weight' => null,
            'health_status' => null,
            'external_key' => null,
            'issue_type' => 'issue',
            'state' => $this->getState($issue),
            'events' => [],
            'timelogs' => [],
            'notes' => [],
            'label_links' => [],
            'resource_label_events' => [],
            'designs' => [],
            'design_versions' => [],
            'issue_assignees' => [],
            'zoom_meetings' => [],
            'award_emoji' => [],
        ];
    }

    private function getAuthorId(Issue $issue): int
    {
        return $issue->getUserId();
    }

    private function getState(Issue $issue): string
    {
        return $issue->getStatus()->isClosed() ? 'closed' : 'opened';
    }
}
