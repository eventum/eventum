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

use Eventum\Attachment\AttachmentManager;
use Eventum\Db\DatabaseException;
use Eventum\Mail\MailMessage;

/**
 * Class to handle the business logic related to adding, updating or
 * deleting notes from the application.
 */
class Note
{
    /**
     * Returns the next and previous notes associated with the given issue ID
     * and the currently selected note.
     *
     * @param   int $issue_id The issue ID
     * @param   int $not_id The currently selected note ID
     * @return  array The next and previous note ID
     */
    public static function getSideLinks($issue_id, $not_id)
    {
        $stmt = 'SELECT
                    not_id
                 FROM
                    `note`
                 WHERE
                    not_iss_id=? AND
                    not_removed = 0
                 ORDER BY
                    not_created_date ASC';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $index = array_search($not_id, $res);
        if (!empty($res[$index + 1])) {
            $next = $res[$index + 1];
        }
        if (!empty($res[$index - 1])) {
            $previous = $res[$index - 1];
        }

        return [
            'next' => @$next,
            'previous' => @$previous,
        ];
    }

    /**
     * Retrieves the details about a given note.
     *
     * @param   int $note_id The note ID
     * @return  bool|array The note details
     */
    public static function getDetails($note_id)
    {
        $stmt = 'SELECT
                    `note`.*,
                    not_full_message,
                    usr_full_name
                 FROM
                    `note`,
                    `user`
                 WHERE
                    not_usr_id=usr_id AND
                    not_id=?';

        $res = DB_Helper::getInstance()->getRow($stmt, [$note_id]);
        if (!$res) {
            throw new InvalidArgumentException("Could not fetch note: $note_id");
        }

        $res['timestamp'] = Date_Helper::getUnixTimestamp($res['not_created_date'], 'GMT');
        $res['has_blocked_message'] = $res['not_is_blocked'] == 1;

        if (!empty($res['not_unknown_user'])) {
            $res['not_from'] = $res['not_unknown_user'];
        } else {
            $res['not_from'] = User::getFullName($res['not_usr_id']);
        }
        if ($res['not_has_attachment']) {
            $mail = MailMessage::createFromString($res['not_full_message']);
            $res['attachments'] = $mail->getAttachment()->getAttachments();
        }

        return $res;
    }

    /**
     * Returns the sequential note identification number for the given issue.
     * This is only for display purposes, but has become relied upon by users
     * as a valid reference number.  It is simply a sequence, starting with the
     * first note created as #1, and each increasing by 1 there after.
     */
    public static function getNoteSequenceNumber($issue_id, $note_id)
    {
        static $issue_note_numbers;

        if (isset($issue_note_numbers[$issue_id][$note_id])) {
            return $issue_note_numbers[$issue_id][$note_id];
        }

        $stmt = 'SELECT
                    not_id,
                    not_iss_id
                FROM
                    `note`
                WHERE
                    not_iss_id = ? AND
                    not_removed = 0
                ORDER BY
                    not_created_date ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $sequence_number = 1;
        foreach ($res as $note_issue_ids) {
            $issue_note_numbers[$note_issue_ids['not_iss_id']][$note_issue_ids['not_id']] = $sequence_number;
            $sequence_number++;
        }

        if (isset($issue_note_numbers[$issue_id][$note_id])) {
            return $issue_note_numbers[$issue_id][$note_id];
        }

        return '#';
    }

    /**
     * Returns the blocked email message associated with the given note ID.
     *
     * @param   int $note_id The note ID
     * @return MailMessage
     */
    public static function getBlockedMessage($note_id)
    {
        $stmt = 'SELECT
                    not_full_message
                 FROM
                    `note`
                 WHERE
                    not_id=?';

        $blocked_message = DB_Helper::getInstance()->getOne($stmt, [$note_id]);

        return MailMessage::createFromString($blocked_message);
    }

    /**
     * Returns the issue ID associated with the given note ID.
     *
     * @param   int $note_id The note ID
     * @return  int The issue ID
     */
    public static function getIssueID($note_id)
    {
        $stmt = 'SELECT
                    not_iss_id
                 FROM
                    `note`
                 WHERE
                    not_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$note_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Returns the nth note for the specific issue. Sequence starts at 1.
     *
     * @param   int $issue_id the id of the issue
     * @param   int $sequence the sequential number of the note
     * @return  array an array of data containing details about the note
     */
    public static function getNoteBySequence($issue_id, $sequence)
    {
        $offset = (int) $sequence - 1;
        $stmt = "SELECT
                    not_id
                FROM
                    `note`
                WHERE
                    not_iss_id = ? AND
                    not_removed = 0
                 ORDER BY
                    not_created_date ASC
                LIMIT 1 OFFSET $offset";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return self::getDetails($res);
    }

    /**
     * Method used to get the unknown_user from the note table for the specified note id.
     *
     * @param   int $note_id The note ID
     * @return string
     */
    public static function getUnknownUser($note_id)
    {
        $sql = 'SELECT
                    not_unknown_user
                FROM
                    `note`
                 WHERE
                    not_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$note_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to add a note using the user interface form
     * available in the application.
     *
     * @param   int $usr_id The user ID
     * @param   int $issue_id The issue ID
     * @param   string  $unknown_user The email address of a user that sent the blocked email that was turned into this note. Default is false.
     * @param   bool $log If adding this note should be logged. Default true.
     * @param   bool $closing If The issue is being closed. Default false
     * @param   bool $send_notification Whether to send a notification about this note or not
     * @param bool $is_blocked
     * @return  int the new note id if the insert worked, -1 or -2 otherwise
     * @deprecated use insertNote() instead
     */
    public static function insertFromPost($usr_id, $issue_id, $unknown_user = null, $log = true, $closing = false, $send_notification = true, $is_blocked = false)
    {
        $options = [
            'unknown_user' => $unknown_user,
            'log' => $log,
            'closing' => $closing,
            'send_notification' => $send_notification,
            'is_blocked' => $is_blocked,

            'message_id' => !empty($_POST['message_id']) ? $_POST['message_id'] : null,
            'full_message' => !empty($_POST['full_message']) ? $_POST['full_message'] : null,
            'parent_id' => !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
            'add_extra_recipients' => isset($_POST['add_extra_recipients']) ? $_POST['add_extra_recipients'] == 'yes' : false,
            'cc' => !empty($_POST['note_cc']) ? $_POST['note_cc'] : null,
        ];

        return self::insertNote($usr_id, $issue_id, $_POST['title'], $_POST['note'], $options);
    }

    /**
     * Insert note to system, send out notification and log.
     *
     * @param int $usr_id The user ID
     * @param int $issue_id The issue ID
     * @param string $title Title of the note
     * @param string $note Note contents
     * @param array $options extra optional options:
     * - (array) cc: extra recipients to notify (usr_id list)
     * - (bool) add_extra_recipients: whether to add recipients in 'cc' to notification list
     * - (bool) closing: If The issue is being closed. Default false
     * - (bool) is_blocked: FIXME
     * - (bool) log: If adding this note should be logged. Default true
     * - (bool) send_notification: Whether to send a notification about this note or not. Default true
     * - (int) parent_id: FIXME
     * - (string) full_message: FIXME
     * - (string) message_id: FIXME
     * - (string) unknown_user: The email address of a user that sent the blocked email that was turned into this note
     * @return int the new note id if the insert worked, -1 or -2 otherwise
     */
    public static function insertNote($usr_id, $issue_id, $title, $note, $options = [])
    {
        if (Validation::isWhitespace($note)) {
            return -2;
        }

        $options = array_merge([
            'unknown_user' => null,
            'log' => true,
            'closing' => false,
            'send_notification' => true,
            'is_blocked' => false,
            'add_extra_recipients' => false,

            'message_id' => null,
            'cc' => [],
            'full_message' => null,
            'parent_id' => null,
        ], $options);

        $prj_id = Issue::getProjectID($issue_id);
        // NOTE: workflow may modify the parameters as $data is passed as reference
        $data = [
            'title' => &$title,
            'note' => &$note,
            'options' => $options,
        ];
        $workflow = Workflow::preNoteInsert($prj_id, $issue_id, $data);
        if ($workflow !== null) {
            // cancel insert of note
            return $workflow;
        }

        // add the poster to the list of people to be subscribed to the notification list
        // only if there is no 'unknown user' and the note is not blocked
        if (!$options['unknown_user'] && !$options['is_blocked']) {
            $note_cc = $options['add_extra_recipients'] ? $options['cc'] : [];
            // always add the current user to the note_cc list
            $note_cc[] = $usr_id;

            $actions = Notification::getDefaultActions($issue_id, User::getEmail($usr_id), 'note');
            foreach ($note_cc as $subscriber_usr_id) {
                Notification::subscribeUser($usr_id, $issue_id, $subscriber_usr_id, $actions);
            }
        }

        $params = [
            'not_iss_id' => $issue_id,
            'not_usr_id' => $usr_id,
            'not_created_date' => Date_Helper::getCurrentDateGMT(),
            'not_note' => $note,
            'not_title' => $title,
            'not_message_id' => $options['message_id'] ?: Mail_Helper::generateMessageID(),
        ];

        if ($options['full_message']) {
            $params['not_full_message'] = $options['full_message'];
        }

        if ($options['is_blocked']) {
            $params['not_is_blocked'] = '1';
        }

        if ($options['parent_id']) {
            $params['not_parent_id'] = $options['parent_id'];
        }

        if ($options['unknown_user']) {
            $params['not_unknown_user'] = $options['unknown_user'];
        }

        $stmt = 'INSERT INTO
                    `note`
                 SET ' . DB_Helper::buildSet($params);

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $note_id = DB_Helper::get_last_insert_id();
        Issue::markAsUpdated($issue_id, 'note');
        if ($options['log']) {
            // need to save a history entry for this
            if ($options['is_blocked']) {
                History::add($issue_id, $usr_id, 'email_blocked', "Email from '{from}' blocked", [
                    'from' => User::getFromHeader($usr_id),
                ]);
            } else {
                History::add($issue_id, $usr_id, 'note_added', 'Note added by {subject}', ['subject' => User::getFullName($usr_id)]);
            }
        }

        // send notifications for the issue being updated
        if ($options['send_notification']) {
            $internal_only = true;
            Notification::notify($issue_id, 'notes', $note_id, $internal_only, $options['cc']);
            Workflow::handleNewNote($prj_id, $issue_id, $usr_id, $options['closing'], $note_id);
        }

        // need to return the new note id here so it can
        // be re-used to associate internal-only attachments
        return $note_id;
    }

    /**
     * Method used to remove a specific note from the application.
     *
     * @param   int $note_id The note ID
     * @param   bool $log If this event should be logged or not. Default true
     * @return  int 1 if the removal worked, -1 or -2 otherwise
     */
    public static function remove($note_id, $log = true)
    {
        $stmt = 'SELECT
                    not_iss_id,
                    not_usr_id,
                    not_is_blocked AS has_blocked_message
                 FROM
                    `note`
                 WHERE
                    not_id=?';

        $details = DB_Helper::getInstance()->getRow($stmt, [$note_id]);
        if ($details['not_usr_id'] != Auth::getUserID() && $details['has_blocked_message'] != 1 && Auth::getCurrentRole() < User::ROLE_MANAGER) {
            return -2;
        }

        // Notes are not deleted so a record of the the not_message_id is
        // preserved to prevent duplicates from being downloaded.
        $stmt = 'UPDATE
                    `note`
                 SET
                    not_removed = 1
                 WHERE
                    not_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$note_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        // also remove any internal-only files associated with this note
        $attachment_groups = AttachmentManager::getList($details['not_iss_id'], User::ROLE_USER, $note_id);
        foreach ($attachment_groups as $group_info) {
            $group = AttachmentManager::getGroup($group_info['iat_id']);
            $group->delete(true);
        }

        Issue::markAsUpdated($details['not_iss_id']);
        if ($log) {
            // need to save a history entry for this
            $usr_id = Auth::getUserID();
            History::add($details['not_iss_id'], $usr_id, 'note_removed', 'Note removed by {user}', [
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Method used to get the full listing of notes associated with
     * a specific issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of notes
     */
    public static function getListing($issue_id)
    {
        $stmt = 'SELECT
                    not_id,
                    not_created_date,
                    not_title,
                    not_usr_id,
                    not_unknown_user,
                    not_has_attachment,
                    not_is_blocked AS has_blocked_message,
                    usr_full_name
                 FROM
                    `note`,
                    `user`
                 WHERE
                    not_usr_id=usr_id AND
                    not_iss_id=? AND
                    not_removed = 0
                 ORDER BY
                    not_created_date ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        // only show the internal notes for users with the appropriate permission level
        $role_id = Auth::getCurrentRole();
        $user_role_id = User::ROLE_USER;
        $t = [];
        foreach ($res as &$row) {
            if ($role_id < $user_role_id) {
                continue;
            }

            // Display not_unknown_user instead of usr_full_name if not null.
            // This is so the original sender of a blocked email is displayed on the note.
            if (!empty($row['not_unknown_user'])) {
                $row['usr_full_name'] = $row['not_unknown_user'];
            }

            $t[] = $row;
            unset($row);
        }

        return $t;
    }

    /**
     * Converts a note to a draft or an email
     *
     * @param int $note_id The id of the note
     * @param string $target What the note should be converted too (email, etc)
     * @param bool $authorize_sender if $authorize_sender If the sender should be added to authorized senders list
     * @return int
     */
    public static function convertNote($note_id, $target, $authorize_sender = false)
    {
        $issue_id = self::getIssueID($note_id);
        $email_account_id = Email_Account::getEmailAccount();
        $mail = self::getBlockedMessage($note_id);
        $unknown_user = self::getUnknownUser($note_id);
        $sender_email = $mail->getSender();
        $usr_id = Auth::getUserID();

        if ($target == 'email') {
            Mail_Helper::rewriteThreadingHeaders($mail, $issue_id);
            $email_options = [
                'issue_id' => $issue_id,
                'ema_id' => $email_account_id,
                'date' => Date_Helper::convertDateGMT($mail->getDate()),
                // these below are likely unused by Support::insertEmail
                'message_id' => $mail->messageId,
                'from' => $mail->from,
                'to' => $mail->to,
                'cc' => $mail->cc,
                'subject' => $mail->subject,
                'body' => $mail->getMessageBody(),
                'full_email' => $mail->getRawContent(), // for Notification::notifyNewEmail
                'headers' => $mail->getHeadersArray(), // for Notification::notifyNewEmail
            ];

            // need to check for a possible customer association
            if ($mail->from) {
                // FIXME: how can 'from' be missing?
                $details = Email_Account::getDetails($email_account_id);
                // check from the associated project if we need to lookup any customers by this email address
                if (CRM::hasCustomerIntegration($details['ema_prj_id'])) {
                    $crm = CRM::getInstance($details['ema_prj_id']);
                    // check for any customer contact association
                    try {
                        $contact = $crm->getContactByEmail($sender_email);
                        $issue_contract = $crm->getContract(Issue::getContractID($issue_id));
                        if ($contact->canAccessContract($issue_contract)) {
                            $email_options['customer_id'] = $issue_contract->getCustomerID();
                        }
                    } catch (CRMException $e) {
                    }
                }
            }
            if (empty($email_options['customer_id'])) {
                $update_type = 'staff response';
                $email_options['customer_id'] = null;
            } else {
                $update_type = 'customer action';
            }

            $sup_id = Support::insertEmail($mail, $email_options);
            if ($sup_id) {
                Support::extractAttachments($issue_id, $mail);
                // notifications about new emails are always external
                // special case when emails are bounced back, so we don't want to notify the customer about those
                $email_options['internal_only'] = $mail->isBounceMessage();
                $email_options['sup_id'] = $sup_id;
                $email_options['usr_id'] = $usr_id;
                $email_options['issue_id'] = $issue_id;
                Notification::notifyNewEmail($mail, $email_options);
                Issue::markAsUpdated($issue_id, $update_type);
                self::remove($note_id, false);
                History::add($issue_id, $usr_id, 'note_converted_email', 'Note converted to e-mail (from: {from}) by {user}', [
                    'from' => $mail->from,
                    'user' => User::getFullName($usr_id),
                ]);
                // now add sender as an authorized replier
                if ($authorize_sender) {
                    Authorized_Replier::manualInsert($issue_id, $mail->from);
                }
            }

            return $sup_id ? 1 : -1;
        }

        // save message as a draft
        $res = Draft::saveEmail($issue_id,
            $mail->to,
            $mail->cc,
            $mail->subject,
            $mail->getMessageBody(),
            false, $unknown_user);

        // remove the note, if the draft was created successfully
        if ($res) {
            self::remove($note_id, false);
            History::add($issue_id, $usr_id, 'note_converted_draft', 'Note converted to draft (from: {from}) by {user}', [
                'from' => $mail->from,
                'user' => User::getFullName($usr_id),
            ]);
        }

        return $res;
    }

    /**
     * Returns the number of notes by a user in a time range.
     *
     * @param   string $usr_id The ID of the user
     * @param   int $start The timestamp of the start date
     * @param   int $end The timestanp of the end date
     * @return  int The number of notes by the user
     */
    public static function getCountByUser($usr_id, $start, $end)
    {
        $stmt = 'SELECT
                    COUNT(not_id)
                 FROM
                    `note`,
                    `issue`
                 WHERE
                    not_iss_id = iss_id AND
                    iss_prj_id = ? AND
                    not_created_date BETWEEN ? AND ? AND
                    not_usr_id = ? AND
                    not_removed = 0';
        $params = [Auth::getCurrentProject(), $start, $end, $usr_id];
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to mark a note as having attachments associated with it.
     *
     * @param   int $note_id The note ID
     * @return  bool
     */
    public static function setAttachmentFlag($note_id)
    {
        $stmt = 'UPDATE
                    `note`
                 SET
                    not_has_attachment=1
                 WHERE
                    not_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$note_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the issue ID associated with a given note
     * message-id.
     *
     * @param   string $message_id The message ID
     * @return  int The issue ID
     */
    public static function getIssueByMessageID($message_id)
    {
        if (!$message_id) {
            return false;
        }
        $stmt = 'SELECT
                    not_iss_id
                 FROM
                    `note`
                 WHERE
                    not_message_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$message_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Returns the message-id of the parent note.
     *
     * @param   string $message_id The message ID
     * @return  string The message id of the parent note or false
     */
    public static function getParentMessageIDbyMessageID($message_id)
    {
        if (!$message_id) {
            return false;
        }
        $sql = 'SELECT
                    parent.not_message_id
                FROM
                    `note` child,
                    `note` parent
                WHERE
                    parent.not_id = child.not_parent_id AND
                    child.not_message_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$message_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if (empty($res)) {
            return false;
        }

        return $res;
    }

    /**
     * Method used to get the note ID associated with a given note
     * message-id.
     *
     * @param   string $message_id The message ID
     * @return  int The note ID
     */
    public static function getIDByMessageID($message_id)
    {
        if (!$message_id) {
            return false;
        }
        $stmt = 'SELECT
                    not_id
                 FROM
                    `note`
                 WHERE
                    not_message_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$message_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if (empty($res)) {
            return false;
        }

        return $res;
    }

    /**
     * Method used to get the message-ID associated with a given note
     * id.
     *
     * @param   int $id The ID
     * @return  string The Message-ID
     */
    public static function getMessageIDbyID($id)
    {
        $stmt = 'SELECT
                    not_message_id
                 FROM
                    `note`
                 WHERE
                    not_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if (empty($res)) {
            return false;
        }

        return $res;
    }

    /**
     * Checks if a message already is downloaded..
     *
     * @param   string $message_id The Message-ID header
     * @return  bool
     */
    public static function exists($message_id)
    {
        $sql = 'SELECT
                    count(*)
                FROM
                    `note`
                WHERE
                    not_message_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$message_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if ($res > 0) {
            return true;
        }

        return false;
    }
}
