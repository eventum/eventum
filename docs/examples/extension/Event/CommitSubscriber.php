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

namespace Eventum\Event;

use Eventum\Model\Entity;
use Notification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class CommitSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::SCM_COMMIT_BEFORE => 'onCommit',
            SystemEvents::SCM_COMMIT_ASSOCIATED => 'onAssociate',
        ];
    }

    public function onCommit(GenericEvent $event)
    {
        /** @var Entity\Commit $commit */
        $commit = $event->getSubject();

        if (!$commit->getUserId()) {
            // XXX: complex logic figuring out user id
            $usr_id = APP_SYSTEM_USER_ID;
            $commit->setUserId($usr_id);
        }
    }

    public function onAssociate(GenericEvent $event)
    {
        /** @var Entity\Commit $commit */
        $commit = $event->getSubject();

        $issue = $commit->getIssue();
        $issue_id = $issue->getId();
        $prj_id = $issue->getProjectId();

        // XXX: complex logic figuring out what to say to IRC
        $irc_message = sprintf('commits added to #%d', $issue_id);

        Notification::notifyIRC($prj_id, $irc_message, $issue_id);
    }
}
