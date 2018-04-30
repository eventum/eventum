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

namespace Example\Event;

use Eventum\Event\SystemEvents;
use Eventum\Mail\MailMessage;
use Group;
use Issue;
use Misc;
use Notification;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use User;

class IrcSubscriber implements EventSubscriberInterface
{
    /*
     * Higher equals more important and therefore that the listener will be triggered earlier
     */
    const PRIORITY_EARLY = 100;

    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::EMAIL_BLOCKED => 'emailBlocked',
            SystemEvents::ISSUE_ASSIGNMENT_CHANGE => 'assignmentChange',
            SystemEvents::ISSUE_CLOSED => 'issueClosed',
            SystemEvents::MAIL_PENDING => 'mailPending',
            SystemEvents::NOTIFY_ISSUE_CREATED => ['notifyIssueCreated', self::PRIORITY_EARLY],
        ];
    }

    /**
     * @param GenericEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     */
    public function emailBlocked(GenericEvent $event, $eventName, $dispatcher)
    {
        $issue_id = $event['issue_id'];
        $prj_id = $event['prj_id'];
        $email_details = $event['email_details'];
        $from = $email_details['from'];

        /**
         * @see Notification::notifyIRCBlockedMessage
         * @see \Eventum\Event\IrcSubscriber::notifyBlockedMessage
         */
        $notice = "Issue #$issue_id updated (";
        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $notice .= 'Assignment: ' . implode(', ', $assignment) . '; ';
        }
        $notice .= "BLOCKED email from '$from')";

        Notification::notifyIRC($prj_id, $notice, $issue_id);
    }

    public function assignmentChange(GenericEvent $event)
    {
        $new_assignees = $event['new_assignees'];

        // do not notify about clearing the assignment of an issue
        if (count($new_assignees) === 0) {
            return;
        }

        // only notify on irc if the assignment is being done to more than one person,
        // or in the case of a one-person-assignment-change, if the person doing it
        // is different than the actual assignee
        if (count($new_assignees) === 1 && $new_assignees[0] == $event['usr_id']) {
            return;
        }

        $old_assignees = $event['issue_details']['assigned_users'];
        $assign_diff = Misc::arrayDiff($old_assignees, $new_assignees);
        if (count($assign_diff) <= 0 && count($new_assignees) === count($old_assignees)) {
            return;
        }

        $notice = "Issue #{$event['issue_id']} ";
        if ($event['remote_assignment']) {
            $notice .= 'remotely ';
        }

        $notice .= 'updated (Old Assignment: ' . $old_assignees;
        if (count($old_assignees) === 0) {
            $notice .= '[empty]';
        } else {
            $notice .= implode(', ', User::getFullName($old_assignees));
        }

        $notice .= '; New Assignment: ' . implode(', ', User::getFullName($new_assignees)) . ')';

        Notification::notifyIRC($event['prj_id'], $notice, $event['issue_id']);
    }

    public function issueClosed(GenericEvent $event)
    {
        $prj_id = $event['prj_id'];
        $issue_id = $event['issue_id'];
        $issue_details = $event['issue_details'];

        $fields = [];
        $fields[] = 'Status: ' . $issue_details['sta_title'];
        $fields[] = 'Resolution: ' . $issue_details['iss_resolution'];

        $irc_message = "Issue #$issue_id closed";
        $irc_message .= ' (' . implode('; ', $fields) . ')';
        $irc_message .= ' ' . $issue_details['iss_summary'];

        Notification::notifyIRC($prj_id, $irc_message, $issue_id);
    }

    public function mailPending(GenericEvent $event)
    {
        /** @var MailMessage $mail */
        $mail = $event->getSubject();
        $prj_id = $event['prj_id'];
        $subject = $mail->subject;

        // email not associated with issue
        $irc_message = "New Pending Email (Subject: {$subject})";
        Notification::notifyIRC($prj_id, $irc_message, 0);
    }

    /**
     * Notify new issue to irc channel
     *
     * @param GenericEvent $event
     */
    public function notifyIssueCreated(GenericEvent $event)
    {
        // avoid calling eventum own handler
        $event['irc_legacy_handled'] = true;

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
}
