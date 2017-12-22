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

use Date_Helper;
use DB_Helper;
use Group;
use Issue;
use Notification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Workflow;

class IrcSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::IRC_NOTIFY => 'notifyIRC',
            SystemEvents::NOTIFY_ISSUE_CREATED => 'notifyIssueCreated',
        ];
    }

    /**
     * Notify new issue to irc channel
     */
    public function notifyIssueCreated(GenericEvent $event)
    {
        $issue_id = $event['issue_id'];
        $prj_id = $event['prj_id'];
        $data = $event['data'];

        $irc_notice = "New Issue #$issue_id (";
        $quarantine = Issue::getQuarantineInfo($issue_id);
        if ($quarantine) {
            $irc_notice .= 'Quarantined; ';
        }

        $irc_notice .= 'Priority: ' . $data['pri_title'];

        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $irc_notice .= '; Assignment: ' . implode(', ', $assignment);
        }

        if (!empty($data['iss_grp_id'])) {
            $irc_notice .= '; Group: ' . Group::getName($data['iss_grp_id']);
        }
        $irc_notice .= '), ';

        if (isset($data['customer'])) {
            $irc_notice .= $data['customer']['name'] . ', ';
        }

        $irc_notice .= $data['iss_summary'];

        Notification::notifyIRC($prj_id, $irc_notice, $issue_id, false, false, 'new_issue');
    }

    public function notifyIRC(GenericEvent $event)
    {
        $notice = Workflow::formatIRCMessage(
            $event['prj_id'], $event['notice'], $event['issue_id'],
            $event['usr_id'], $event['category'], $event['type']
        );

        if ($notice === false) {
            return;
        }

        $params = [
            'ino_prj_id' => $event['prj_id'],
            'ino_created_date' => Date_Helper::getCurrentDateGMT(),
            'ino_status' => 'pending',
            'ino_message' => $event['notice'],
            'ino_category' => $event['category'],
        ];

        if ($event['issue_id']) {
            $params['ino_iss_id'] = $event['issue_id'];
        }
        if ($event['usr_id']) {
            $params['ino_target_usr_id'] = $event['usr_id'];
        }

        $stmt = 'INSERT INTO `irc_notice` SET ' . DB_Helper::buildSet($params);
        DB_Helper::getInstance()->query($stmt, $params);
    }
}
