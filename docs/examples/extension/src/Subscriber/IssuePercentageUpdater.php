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

namespace Example\Subscriber;

use Eventum\Event\EventContext;
use Eventum\Event\SystemEvents;
use Eventum\TaskList\TaskListItem;
use Eventum\TaskList\TaskListMatcher;
use Issue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IssuePercentageUpdater implements EventSubscriberInterface
{
    /** @var TaskListMatcher */
    private $matcher;

    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::ISSUE_UPDATED => 'updateIssueComplete',
            SystemEvents::ISSUE_CREATED => 'updateIssueComplete',
        ];
    }

    public function __construct()
    {
        $this->matcher = new TaskListMatcher();
    }

    public function updateIssueComplete(EventContext $event): void
    {
        $complete = $this->getTaskComplete($event['issue_details']['iss_description']);
        if ($complete === null) {
            // no tasks, do not update
            return;
        }

        Issue::setIssueCompletePercentage($event->getIssueId(), $complete);
    }

    /**
     * Get percentage of complete tasks, or null if no tasks present
     *
     * @param string $content
     * @return int|null
     */
    private function getTaskComplete(string $content): ?int
    {
        $tasks = iterator_to_array($this->matcher->getTasks($content));
        if (!$tasks) {
            return null;
        }

        $complete = array_filter(array_map(static function (TaskListItem $c) {
            return $c->isChecked();
        }, $tasks));

        return count($complete) / count($tasks) * 100;
    }
}
