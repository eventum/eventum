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

namespace Example\Event\Subscriber;

use Eventum\Event\SystemEvents;
use Eventum\Model\Entity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class CommitSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::SCM_COMMIT_BEFORE => 'onCommit',
            SystemEvents::SCM_COMMIT_ASSOCIATED => 'onAssociate',
        ];
    }

    public function onCommit(GenericEvent $event): void
    {
        /** @var Entity\Commit $commit */
        $commit = $event->getSubject();

        if (!$commit->getUserId()) {
            // XXX: complex logic figuring out user id
            $usr_id = APP_SYSTEM_USER_ID;
            $commit->setUserId($usr_id);
        }
    }

    public function onAssociate(GenericEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var Entity\Commit $commit */
        $commit = $event->getSubject();

        $issue = $commit->getIssue();
        $issue_id = $issue->getId();
        $prj_id = $issue->getProjectId();

        // XXX: complex logic figuring out what to say to IRC
        $irc_message = sprintf('commits added to #%d', $issue_id);

        $this->notifyIrc($dispatcher, $event, $irc_message);
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param GenericEvent $sourceEvent
     * @param string $notice
     */
    private function notifyIrc(EventDispatcherInterface $dispatcher, GenericEvent $sourceEvent, $notice): void
    {
        $arguments = [
            'prj_id' => $sourceEvent['project_id'],
            'issue_id' => $sourceEvent['issue_id'],
            'notice' => $notice,
            'usr_id' => null,
            'category' => false,
            'type' => false,
        ];

        $event = new GenericEvent(null, $arguments);
        $dispatcher->dispatch(SystemEvents::IRC_NOTIFY, $event);
    }
}
