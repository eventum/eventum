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
use Reminder;
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
            SystemEvents::IRC_NOTIFY_BLOCKED_MESSAGE => 'notifyBlockedMessage',
            SystemEvents::NOTIFY_ISSUE_CREATED => 'notifyIssueCreated',
            SystemEvents::REMINDER_ACTION_PERFORM => 'reminderAction',
        ];
    }

    /**
     * Method used to send an IRC notification about a blocked email that was
     * saved into an internal note.
     */
    public function notifyBlockedMessage(GenericEvent $event)
    {
        $issue_id = $event['issue_id'];
        $from = $event['from'];

        $notice = "Issue #$issue_id updated (";
        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $notice .= 'Assignment: ' . implode(', ', $assignment) . '; ';
        }
        $notice .= "BLOCKED email from '$from')";

        $prj_id = Issue::getProjectID($issue_id);
        Notification::notifyIRC($prj_id, $notice, $issue_id);
    }

    public function reminderAction(GenericEvent $event)
    {
        $issue_id = $event['issue_id'];
        $action = $event['action'];

        // alert IRC if needed
        if (!$action['rma_alert_irc']) {
            return;
        }

        $irc_notice = "Issue #$issue_id (";
        if (!empty($data['pri_title'])) {
            $irc_notice .= 'Priority: ' . $data['pri_title'];
        }
        if (!empty($data['sev_title'])) {
            $irc_notice .= 'Severity: ' . $data['sev_title'];
        }
        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $irc_notice .= '; Assignment: ' . implode(', ', $assignment);
        }
        if (!empty($data['iss_grp_id'])) {
            $irc_notice .= '; Group: ' . Group::getName($data['iss_grp_id']);
        }
        $irc_notice .= "), Reminder action '" . $action['rma_title'] . "' was just triggered; " . $action['rma_boilerplate'];

        $prj_id = Issue::getProjectID($issue_id);
        Notification::notifyIRC($prj_id, $irc_notice, $issue_id, false, APP_EVENTUM_IRC_CATEGORY_REMINDER);
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
        $category = $event['category'];
        $notice = Workflow::formatIRCMessage(
            $event['prj_id'], $event['notice'], $event['issue_id'],
            $event['usr_id'], $category, $event['type']
        );
        // assign back in case workflow modified value
        $event['category'] = $category;

        if ($notice === false) {
            return;
        }

        $params = [
            'ino_prj_id' => $event['prj_id'],
            'ino_created_date' => Date_Helper::getCurrentDateGMT(),
            'ino_status' => 'pending',
            'ino_message' => $event['notice'],
            'ino_category' => $category,
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
