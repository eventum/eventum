<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

class Draft
{
    /**
     * Method used to save the routed draft into a backup directory.
     *
     * @param   string $message The full body of the draft
     */
    public static function saveRoutedMessage($message)
    {
        if (!defined('APP_ROUTED_MAILS_SAVEDIR') || !APP_ROUTED_MAILS_SAVEDIR) {
            return;
        }
        list($usec) = explode(' ', microtime());
        $filename = date('Y-m-d_H-i-s_') . $usec . '.draft.txt';
        $file = APP_ROUTED_MAILS_SAVEDIR . '/routed_drafts/' . $filename;
        file_put_contents($file, $message);
        chmod($file, 0644);
    }

    /**
     * Method used to save the draft response in the database for
     * further use.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $to The primary recipient of the draft
     * @param   string $cc The secondary recipients of the draft
     * @param   string $subject The subject of the draft
     * @param   string $message The draft body
     * @param   integer $parent_id The ID of the email that this draft is replying to, if any
     * @param   string $unknown_user The sender of the draft, if not a real user
     * @param   boolean $add_history_entry Whether to add a history entry automatically or not
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function saveEmail($issue_id, $to, $cc, $subject, $message, $parent_id = null, $unknown_user = null, $add_history_entry = true)
    {
        if (!$parent_id) {
            $parent_id = null;
        }
        // if unknown_user is not empty, set the usr_id to be the system user.
        if (!empty($unknown_user)) {
            $usr_id = APP_SYSTEM_USER_ID;
        } else {
            $usr_id = Auth::getUserID();
        }
        $stmt = 'INSERT INTO
                    {{%email_draft}}
                 (
                    emd_updated_date,
                    emd_usr_id,
                    emd_iss_id,
                    emd_sup_id,
                    emd_subject,
                    emd_body';

        if ($unknown_user) {
            $stmt .= ', emd_unknown_user';
        }
        $stmt .= ') VALUES (
                    ?, ?, ?, ?, ?, ?
                ';
        $params = array(
            Date_Helper::getCurrentDateGMT(),
            $usr_id,
            $issue_id,
            $parent_id,
            $subject,
            $message,
        );
        if ($unknown_user) {
            $stmt .= ', ?';
            $params[] = $unknown_user;
        }
        $stmt .= ')';
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        $new_emd_id = DB_Helper::get_last_insert_id();
        self::addEmailRecipient($new_emd_id, $to, false);
        $cc = str_replace(',', ';', $cc);
        $ccs = explode(';', $cc);
        foreach ($ccs as $cc) {
            self::addEmailRecipient($new_emd_id, $cc, true);
        }
        Issue::markAsUpdated($issue_id, 'draft saved');
        if ($add_history_entry) {
            History::add($issue_id, $usr_id, 'draft_added', 'Email message saved as a draft by {user}', array(
                'user' => User::getFullName($usr_id)
            ));
        }

        return 1;
    }

    /**
     * Method used to update an existing draft response.
     *
     * @param   integer $issue_id The issue ID
     * @param   integer $emd_id The email draft ID
     * @param   string $to The primary recipient of the draft
     * @param   string $cc The secondary recipients of the draft
     * @param   string $subject The subject of the draft
     * @param   string $message The draft body
     * @param   integer $parent_id The ID of the email that this draft is replying to, if any
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function update($issue_id, $emd_id, $to, $cc, $subject, $message, $parent_id = null)
    {
        if (!$parent_id) {
            $parent_id = null;
        }
        $usr_id = Auth::getUserID();

        // update previous draft and insert new record
        $stmt = "UPDATE
                    {{%email_draft}}
                 SET
                    emd_updated_date=?,
                    emd_status = 'edited'
                 WHERE
                    emd_id=?";
        $params = array(Date_Helper::getCurrentDateGMT(), $emd_id);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        Issue::markAsUpdated($issue_id, 'draft saved');
        History::add($issue_id, $usr_id, 'draft_updated', 'Email message draft updated by {user}', array(
            'user' => User::getFullName($usr_id))
        );
        self::saveEmail($issue_id, $to, $cc, $subject, $message, $parent_id, false, false);

        return 1;
    }

    /**
     * Method used to remove a draft response.
     *
     * @param   integer $emd_id The email draft ID
     * @return  boolean
     */
    public static function remove($emd_id)
    {
        $stmt = "UPDATE
                    {{%email_draft}}
                 SET
                    emd_status = 'sent'
                 WHERE
                    emd_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($emd_id));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove the recipients associated with the given
     * email draft response.
     *
     * @param   integer $emd_id The email draft ID
     * @return  boolean
     */
    public function removeRecipients($emd_id)
    {
        $stmt = 'DELETE FROM
                    {{%email_draft_recipient}}
                 WHERE
                    edr_emd_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, array($emd_id));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to associate a recipient with a given email
     * draft response.
     *
     * @param   integer $emd_id The email draft ID
     * @param   string $email The recipient's email address
     * @param   boolean $is_cc Whether this recipient is in the Cc list for the given draft
     * @return  boolean
     */
    public static function addEmailRecipient($emd_id, $email, $is_cc)
    {
        $is_cc = $is_cc ? 1 : 0;
        $email = trim($email);
        $stmt = 'INSERT INTO
                    {{%email_draft_recipient}}
                 (
                    edr_emd_id,
                    edr_is_cc,
                    edr_email
                 ) VALUES (
                    ?, ?, ?
                 )';
        $params = array(
            $emd_id,
            $is_cc,
            $email,
        );
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the details on a given email draft response.
     *
     * @param   integer $emd_id The email draft ID
     * @return  array The email draft details
     */
    public static function getDetails($emd_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%email_draft}}
                 WHERE
                    emd_id=?';

        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($emd_id));
        } catch (DbException $e) {
            throw new RuntimeException('email not found');
        }

        $res['emd_updated_date'] = Date_Helper::getFormattedDate($res['emd_updated_date']);
        if (!empty($res['emd_unknown_user'])) {
            $res['from'] = $res['emd_unknown_user'];
        } else {
            $res['from'] = User::getFromHeader($res['emd_usr_id']);
        }
        list($res['to'], $res['cc']) = self::getEmailRecipients($emd_id);

        return $res;
    }

    /**
     * Returns a list of drafts associated with an issue.
     *
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $show_all If all draft statuses should be shown
     * @return  array An array of drafts.
     */
    public static function getList($issue_id, $show_all = false)
    {
        $stmt = "SELECT
                    emd_id,
                    emd_usr_id,
                    emd_subject,
                    emd_updated_date,
                    emd_unknown_user,
                    emd_status
                 FROM
                    {{%email_draft}}
                 WHERE
                    emd_iss_id=?\n";
        $params = array($issue_id);

        if ($show_all == false) {
            $stmt .= "AND emd_status = 'pending'\n";
        }
        $stmt .= 'ORDER BY
                    emd_id';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['emd_updated_date'] = Date_Helper::getFormattedDate($row['emd_updated_date']);
            if (!empty($row['emd_unknown_user'])) {
                $row['from'] = $row['emd_unknown_user'];
            } else {
                $row['from'] = User::getFromHeader($row['emd_usr_id']);
            }
            list($row['to']) = self::getEmailRecipients($row['emd_id']);
            if (empty($row['to'])) {
                $row['to'] = 'Notification List';
            }
        }

        return $res;
    }

    /**
     * Method used to get the list of email recipients for a
     * given draft response.
     *
     * @param   integer $emd_id The email draft ID
     * @return  array The list of email recipients
     */
    public static function getEmailRecipients($emd_id)
    {
        $stmt = 'SELECT
                    edr_email,
                    edr_is_cc
                 FROM
                    {{%email_draft_recipient}}
                 WHERE
                    edr_emd_id=?';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($emd_id));
        } catch (DbException $e) {
            return array('', '');
        }

        $to = '';
        $ccs = array();
        foreach ($res as $email => $is_cc) {
            if ($is_cc) {
                $ccs[] = $email;
            } else {
                $to = $email;
            }
        }

        return array(
            $to,
            $ccs,
        );
    }

    /**
     * Returns the nth draft for the specific issue. Sequence starts at 1.
     *
     * @param   integer $issue_id The id of the issue.
     * @param   integer $sequence The sequential number of the draft.
     * @return  array An array of data containing details about the draft.
     */
    public static function getDraftBySequence($issue_id, $sequence)
    {
        $sequence = (int) $sequence;
        if ($sequence < 1) {
            return array();
        }
        $stmt = "SELECT
                    emd_id
                FROM
                    {{%email_draft}}
                WHERE
                    emd_iss_id = ? AND
                    emd_status = 'pending'
                ORDER BY
                    emd_id ASC
                LIMIT 1 OFFSET " . ($sequence - 1);
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($issue_id));
        } catch (DbException $e) {
            return array();
        }

        if (empty($res)) {
            return array();
        }

        return self::getDetails($res);
    }

    /**
     * Converts an email to a draft and sends it.
     *
     * @param   integer $draft_id The id of the draft to send.
     * @return int
     */
    public static function send($draft_id)
    {
        $draft = self::getDetails($draft_id);
        $_POST['issue_id'] = $draft['emd_iss_id'];
        $_POST['subject'] = $draft['emd_subject'];
        $_POST['from'] = User::getFromHeader(Auth::getUserID());
        $_POST['to'] = $draft['to'];
        $_POST['cc'] = @implode(';', $draft['cc']);
        $_POST['message'] = $draft['emd_body'];
        $_POST['ema_id'] = Email_Account::getEmailAccount();
        $res = Support::sendEmailFromPost();
        if ($res == 1) {
            self::remove($draft_id);
        }

        return $res;
    }

    /**
     * Returns the number of drafts by a user in a time range.
     *
     * @param   string $usr_id The ID of the user
     * @param   integer $start The timestamp of the start date
     * @param   integer $end The timestanp of the end date
     * @return  integer The number of note by the user.
     */
    public function getCountByUser($usr_id, $start, $end)
    {
        $stmt = 'SELECT
                    COUNT(emd_id)
                 FROM
                    {{%email_draft}}
                 WHERE
                    emd_updated_date BETWEEN ? AND ? AND
                    emd_usr_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($start, $end, $usr_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }
}
