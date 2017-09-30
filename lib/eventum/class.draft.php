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

use Eventum\Db\DatabaseException;

class Draft
{
    /**
     * Method used to save the draft response in the database for
     * further use.
     *
     * @param   int $issue_id The issue ID
     * @param   string $to The primary recipient of the draft
     * @param   string $cc The secondary recipients of the draft
     * @param   string $subject The subject of the draft
     * @param   string $message The draft body
     * @param   int $parent_id The ID of the email that this draft is replying to, if any
     * @param   string $unknown_user The sender of the draft, if not a real user
     * @param   bool $add_history_entry Whether to add a history entry automatically or not
     * @return  int 1 if the update worked, -1 otherwise
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
                    `email_draft`
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
        $params = [
            Date_Helper::getCurrentDateGMT(),
            $usr_id,
            $issue_id,
            $parent_id,
            $subject,
            $message,
        ];
        if ($unknown_user) {
            $stmt .= ', ?';
            $params[] = $unknown_user;
        }
        $stmt .= ')';
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
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
            History::add($issue_id, $usr_id, 'draft_added', 'Email message saved as a draft by {user}', [
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Method used to update an existing draft response.
     *
     * @param   int $issue_id The issue ID
     * @param   int $emd_id The email draft ID
     * @param   string $to The primary recipient of the draft
     * @param   string $cc The secondary recipients of the draft
     * @param   string $subject The subject of the draft
     * @param   string $message The draft body
     * @param   int $parent_id The ID of the email that this draft is replying to, if any
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function update($issue_id, $emd_id, $to, $cc, $subject, $message, $parent_id = null)
    {
        if (!$parent_id) {
            $parent_id = null;
        }
        $usr_id = Auth::getUserID();

        // update previous draft and insert new record
        $stmt = "UPDATE
                    `email_draft`
                 SET
                    emd_updated_date=?,
                    emd_status = 'edited'
                 WHERE
                    emd_id=?";
        $params = [Date_Helper::getCurrentDateGMT(), $emd_id];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        Issue::markAsUpdated($issue_id, 'draft saved');
        History::add($issue_id, $usr_id, 'draft_updated', 'Email message draft updated by {user}', [
            'user' => User::getFullName($usr_id), ]
        );
        self::saveEmail($issue_id, $to, $cc, $subject, $message, $parent_id, false, false);

        return 1;
    }

    /**
     * Method used to remove a draft response.
     *
     * @param   int $emd_id The email draft ID
     * @return  bool
     */
    public static function remove($emd_id)
    {
        $stmt = "UPDATE
                    `email_draft`
                 SET
                    emd_status = 'sent'
                 WHERE
                    emd_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, [$emd_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to associate a recipient with a given email
     * draft response.
     *
     * @param   int $emd_id The email draft ID
     * @param   string $email The recipient's email address
     * @param   bool $is_cc Whether this recipient is in the Cc list for the given draft
     * @return  bool
     */
    public static function addEmailRecipient($emd_id, $email, $is_cc)
    {
        $is_cc = $is_cc ? 1 : 0;
        $email = trim($email);
        $stmt = 'INSERT INTO
                    `email_draft_recipient`
                 (
                    edr_emd_id,
                    edr_is_cc,
                    edr_email
                 ) VALUES (
                    ?, ?, ?
                 )';
        $params = [
            $emd_id,
            $is_cc,
            $email,
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the details on a given email draft response.
     *
     * @param   int $emd_id The email draft ID
     * @return  array The email draft details
     */
    public static function getDetails($emd_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `email_draft`
                 WHERE
                    emd_id=?';

        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$emd_id]);
        } catch (DatabaseException $e) {
            throw new RuntimeException('email not found');
        }

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
     * @param   int $issue_id the ID of the issue
     * @param   bool $show_all If all draft statuses should be shown
     * @return  array an array of drafts
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
                    `email_draft`
                 WHERE
                    emd_iss_id=?\n";
        $params = [$issue_id];

        if ($show_all == false) {
            $stmt .= "AND emd_status = 'pending'\n";
        }
        $stmt .= 'ORDER BY
                    emd_id';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
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
     * @param   int $emd_id The email draft ID
     * @return  array The list of email recipients
     */
    public static function getEmailRecipients($emd_id)
    {
        $stmt = 'SELECT
                    edr_email,
                    edr_is_cc
                 FROM
                    `email_draft_recipient`
                 WHERE
                    edr_emd_id=?';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$emd_id]);
        } catch (DatabaseException $e) {
            return ['', ''];
        }

        $to = '';
        $ccs = [];
        foreach ($res as $email => $is_cc) {
            if ($is_cc) {
                $ccs[] = $email;
            } else {
                $to = $email;
            }
        }

        return [
            $to,
            $ccs,
        ];
    }

    /**
     * Returns the nth draft for the specific issue. Sequence starts at 1.
     *
     * @param   int $issue_id the id of the issue
     * @param   int $sequence the sequential number of the draft
     * @return  array an array of data containing details about the draft
     */
    public static function getDraftBySequence($issue_id, $sequence)
    {
        $sequence = (int) $sequence;
        if ($sequence < 1) {
            return [];
        }
        $stmt = "SELECT
                    emd_id
                FROM
                    `email_draft`
                WHERE
                    emd_iss_id = ? AND
                    emd_status = 'pending'
                ORDER BY
                    emd_id ASC
                LIMIT 1 OFFSET " . ($sequence - 1);
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        if (empty($res)) {
            return [];
        }

        return self::getDetails($res);
    }

    /**
     * Converts an draft to and email and sends it.
     *
     * @param int $draft_id the id of the draft to send
     * @return int
     */
    public static function send($draft_id)
    {
        $draft = self::getDetails($draft_id);

        $from = User::getFromHeader(Auth::getUserID());
        $to = $draft['to'];
        $cc = implode(';', $draft['cc']);
        $subject = $draft['emd_subject'];
        $options = [
            'ema_id' => Email_Account::getEmailAccount(),
        ];

        $res = Support::sendEmail($draft['emd_iss_id'], null, $from, $to, $cc, $subject, $draft['emd_body'], $options);
        if ($res == 1) {
            self::remove($draft_id);
        }

        return $res;
    }
}
