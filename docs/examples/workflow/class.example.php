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

use Eventum\Attachment\AttachmentGroup;
use Eventum\Db\DatabaseException;
use Eventum\Mail\MailMessage;

/**
 * Example workflow backend class. For example purposes it will print what
 * method is called.
 */
class Example_Workflow_Backend extends Abstract_Workflow_Backend
{
    /**
     * Called when an issue is updated.
     *
     * @param int $prj_id the project ID
     * @param int $issue_id the ID of the issue
     * @param int $usr_id the ID of the user
     * @param array $old_details the old details of the issues
     * @param array $changes The changes that were applied to this issue (the $_POST)
     */
    public function handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        echo "Workflow: Issue Updated<br />\n";
    }

    /**
     * Called when a file is attached to an issue.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who locked the issue
     * @param   AttachmentGroup $attachment_group The attachment object
     */
    public function handleAttachment($prj_id, $issue_id, $usr_id, AttachmentGroup $attachment_group)
    {
        echo "Workflow: File attached<br />\n";
        echo '<ul>';
        foreach ($attachment_group->getAttachments() as $attachment) {
            echo "<li>{$attachment->filename}: {$attachment->filesize}</li>\n";
        }
        echo '</ul>';
    }

    /**
     * Called when the priority of an issue changes.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who locked the issue
     * @param   array $old_details the old details of the issue
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    public function handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        echo "Workflow: Priority Changed<br />\n";
    }

    /**
     * Called when an email is blocked.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   array $email_details Details of the issue
     * @param   string $type what type of blocked email this is
     */
    public function handleBlockedEmail($prj_id, $issue_id, $email_details, $type)
    {
        echo "Workflow: Email Blocked<br />\n";
    }

    /**
     * Called when a note is routed.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id The user ID of the person posting this new note
     * @param   bool $closing If the issue is being closed
     * @param   int $note_id The ID of the new note
     */
    public function handleNewNote($prj_id, $issue_id, $usr_id, $closing, $note_id)
    {
        echo "Workflow: New Note<br />\n";
    }

    /**
     * Called when the assignment on an issue changes.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who assigned the issue
     * @param   array $issue_details the old details of the issue
     * @param   array $new_assignees the new assignees of this issue
     * @param   bool $remote_assignment if this issue was remotely assigned
     */
    public function handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment)
    {
        echo "Workflow: Assignment changed<br />\n";
    }

    /**
     * Called when a new issue is created.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   bool $has_TAM if this issue has a technical account manager
     * @param   bool $has_RR if Round Robin was used to assign this issue
     */
    public function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
        echo "Workflow: New Issue<br />\n";
    }

    /**
     * Updates the existing issue to a different status when an email is
     * manually associated to an existing issue.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id The issue ID
     */
    public function handleManualEmailAssociation($prj_id, $issue_id)
    {
        echo "Workflow: Manually associating email to issue<br />\n";
    }

    /**
     * Called when an email is received.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   MailMessage $mail The Mail object
     * @param   array $row the array of data that was inserted into the database
     * @param   bool $closing if we are closing the issue
     * @since 3.4.0 uses new signature, see #263
     */
    public function handleNewEmail($prj_id, $issue_id, MailMessage $mail, $row, $closing = false)
    {
        echo 'Workflow: New';
        if ($closing) {
            echo ' closing';
        }
        echo " Email<br />\n";
    }

    /**
     * Method is called to return the list of statuses valid for a specific issue.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @return  array an associative array of statuses valid for this issue
     */
    public function getAllowedStatuses($prj_id, $issue_id)
    {
        echo "Workflow: Returning allowed statuses<br />\n";
        $statuses = Status::getAssocStatusList($prj_id, false);
        unset($statuses[4], $statuses[3]);
        // you should perform any logic and remove any statuses you need to here.
        return $statuses;
    }

    /**
     * Called when an attempt is made to add a user or email address to the
     * notification list.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $subscriber_usr_id the ID of the user to subscribe if this is a real user (false otherwise)
     * @param   string $email the email address to subscribe to subscribe (if this is not a real user)
     * @return  array|bool an array of information or true to continue unchanged or false to prevent the user from being added
     */
    public function handleSubscription($prj_id, $issue_id, &$subscriber_usr_id, &$email, &$actions)
    {
        // prevent a certain email address from being added to the notification list.
        if ($email == 'invalidemail@example.com') {
            return false;
        }
        // just for this example, if the usr_id is 99, change the usr_id to 100
        if ($subscriber_usr_id == 99) {
            $subscriber_usr_id = 100;
        }
        // another thing this workflow can do is change the actions a user is subscribed too.
        // we will make sure all users are subscribed to the "email" action.
        if (!in_array('emails', $actions)) {
            $actions[] = 'emails';
        }
        // you can also change the email address being subscribed
        if ($email == 'changethis@example.com') {
            $email = 'changed@example.com';
        }
        // if you want the subscription to be added with no changes, simply return true;
        return true;
    }

    /**
     * Called when issue is closed.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   bool $send_notification Whether to send a notification about this action or not
     * @param   int $resolution_id The resolution ID
     * @param   int $status_id The status ID
     * @param   string $reason The reason for closing this issue
     * @param   int $usr_id The ID of the user closing this issue
     */
    public function handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason, $usr_id)
    {
        $sql = "UPDATE
                    `issue`
                SET
                    iss_percent_complete = '100%'
                WHERE
                    iss_id = ?";
        try {
            DB_Helper::getInstance()->query($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return;
        }

        echo "Workflow: handleIssueClosed<br />\n";
    }

    /**
     * Called when custom fields are updated
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   array $old the custom fields before the update
     * @param   array $new the custom fields after the update
     * @param   array $changed an array containing what was changed
     */
    public function handleCustomFieldsUpdated($prj_id, $issue_id, $old, $new, $changed)
    {
        echo "Workflow: handleCustomFieldsUpdated<br />\n";
    }

    /**
     * Determines if the address should should be emailed.
     *
     * @param   int $prj_id The project ID
     * @param   string $address The email address to check
     * @param   int $issue_id the ID of the issue
     * @param   string $type the type of notification to send
     * @return  bool
     */
    public function shouldEmailAddress($prj_id, $address, $issue_id = null, $type = null)
    {
        if ($address == 'bad_email@example.com') {
            return false;
        }

        return true;
    }

    /**
     * Returns which "issue fields" should be displayed in a given location.
     *
     * @see     class.issue_field.php
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   string  $location The location to display these fields at
     * @return  array   an array of fields to display and their associated options
     */
    public function getIssueFieldsToDisplay($prj_id, $issue_id, $location)
    {
        if ($location == 'post_note') {
            return [
                        'assignee' => [],
                        'custom' => [1],
            ];
        }

        return [];
    }
}
