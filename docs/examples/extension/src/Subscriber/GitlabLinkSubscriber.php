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

use Eventum\Db\Doctrine;
use Eventum\Event\SystemEvents;
use Eventum\Scm\Payload\GitlabPayload;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class GitlabLinkSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::RPC_GITLAB_MATCH_ISSUE => 'issueMatched',
        ];
    }

    public function issueMatched(GenericEvent $event): void
    {
        /** @var GitlabPayload $subject */
        $payload = $event->getSubject();
        $repo = Doctrine::getRemoteLinkRepository();

        $url = $event['url'];
        $gid = $this->getGid($url);
        $title = $this->getTitle($payload);
        foreach ($event['description_matches'] as $match) {
            $repo->addRemoteLink($match['issueId'], $url, $title, $gid);
        }
    }

    /**
     * make references unique per issue/merge-request
     */
    private function getGid($url): string
    {
        return preg_replace('/#.*$/', '', $url);
    }

    private function getTitle(GitlabPayload $payload): string
    {
        return "{$payload->getUsername()} mentioned on {$this->getFormattedTarget($payload)}";
    }

    private function getFormattedTarget(GitlabPayload $payload): string
    {
        switch ($payload->getEventType()) {
            case GitlabPayload::EVENT_TYPE_ISSUE:
                return $this->getIssueTitle($payload);

            case GitlabPayload::EVENT_TYPE_MERGE_REQUEST:
                return $this->getMergeRequestTitle($payload);

            case GitlabPayload::EVENT_TYPE_NOTE:
                return $this->getFormattedNoteable($payload);

            default:
                throw new InvalidArgumentException("Unknown event: {$payload->getEventType()}");
        }
    }

    private function getFormattedNoteable(GitlabPayload $payload): string
    {
        switch ($payload->getNoteableType()) {
            case GitlabPayload::NOTEABLE_TYPE_ISSUE:
                return $this->getIssueTitle($payload);

            case GitlabPayload::NOTEABLE_TYPE_MERGE_REQUEST:
                return $this->getMergeRequestTitle($payload);

            default:
                throw new InvalidArgumentException("Unknown noteable type: {$payload->getNoteableType()}");
        }
    }

    private function getIssueTitle(GitlabPayload $payload): string
    {
        return "GitLab issue {$payload->getProject()}#{$payload->getIssueId()}: {$payload->getTitle()}";
    }

    private function getMergeRequestTitle(GitlabPayload $payload): string
    {
        return "GitLab merge request {$payload->getProject()}!{$payload->getMergeRequestId()}: {$payload->getTitle()}";
    }
}
