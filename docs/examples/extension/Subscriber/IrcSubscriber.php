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

use Eventum\Event\SystemEvents;
use Eventum\Mail\MailMessage;
use Group;
use Issue;
use Misc;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use User;

class IrcSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::EMAIL_BLOCKED => 'emailBlocked',
            SystemEvents::ISSUE_ASSIGNMENT_CHANGE => 'assignmentChange',
            SystemEvents::ISSUE_CLOSED => 'issueClosed',
            SystemEvents::MAIL_PENDING => 'mailPending',
            SystemEvents::NOTIFY_ISSUE_CREATED => 'notifyIssueCreated',
            SystemEvents::REMINDER_ACTION_PERFORM => 'reminderAction',
        ];
    }

    /**
     * @param GenericEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     */
    public function emailBlocked(GenericEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $issue_id = $event['issue_id'];
        $email_details = $event['email_details'];
        $from = $email_details['from'];

        $notice = "Issue #$issue_id updated (";
        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $notice .= 'Assignment: ' . implode(', ', $assignment) . '; ';
        }
        $notice .= "BLOCKED email from '$from')";

        $this->notifyIrc($dispatcher, $event, $notice);
    }

    public function assignmentChange(GenericEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
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

        $this->notifyIrc($dispatcher, $event, $notice);
    }

    public function issueClosed(GenericEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $issue_details = $event['issue_details'];

        $fields = [];
        $fields[] = 'Status: ' . $issue_details['sta_title'];
        $fields[] = 'Resolution: ' . $issue_details['iss_resolution'];

        $irc_message = "Issue #{$event['issue_id']} closed";
        $irc_message .= ' (' . implode('; ', $fields) . ')';
        $irc_message .= ' ' . $issue_details['iss_summary'];

        $this->notifyIrc($dispatcher, $event, $irc_message);
    }

    public function mailPending(GenericEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var MailMessage $mail */
        $mail = $event->getSubject();
        $subject = $mail->subject;

        // email not associated with issue
        $irc_message = "New Pending Email (Subject: {$subject})";
        $this->notifyIrc($dispatcher, $event, $irc_message);
    }

    /**
     * Notify new issue to irc channel
     *
     * @param GenericEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     */
    public function notifyIssueCreated(GenericEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $issue_id = $event['issue_id'];
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
        $this->notifyIrc($dispatcher, $event, $irc_notice, null, 'new_issue');
    }

    /**
     * @param GenericEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     */
    public function reminderAction(GenericEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
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

        $this->notifyIrc($dispatcher, $event, $irc_notice, APP_EVENTUM_IRC_CATEGORY_REMINDER);
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param GenericEvent $sourceEvent
     * @param string $notice
     * @param string $category
     * @param string $type
     */
    private function notifyIrc(EventDispatcherInterface $dispatcher, GenericEvent $sourceEvent, $notice, $category = null, $type = null): void
    {
        $arguments = [
            'prj_id' => $sourceEvent['project_id'],
            'issue_id' => $sourceEvent['issue_id'],
            'notice' => $notice,
            'usr_id' => null,
            'category' => $category ?: false,
            'type' => $type ?: false,
        ];

        $event = new GenericEvent(null, $arguments);
        $dispatcher->dispatch($event, SystemEvents::IRC_NOTIFY);
    }
}
