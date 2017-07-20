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
use Eventum\Mail\MailBuilder;
use Eventum\Mail\MailMessage;
use Eventum\Mail\MailTransport;
use Zend\Mail\AddressList;
use Zend\Mail\Header\To;

class Mail_Queue
{
    /**
     * Number of times to retry 'error' status mails before giving up.
     */
    const MAX_RETRIES = 20;

    /**
     * The method exists here to kill Mail_Helper::send() method.
     *
     * @see Mail_Helper::send()
     * @param MailBuilder $builder
     * @param string $to
     * @param array $options
     * @param array $options Optional options:
     * - string $from From address, defaults to system user
     * - integer $save_email_copy Whether to send a copy of this email to a configurable address or not (eventum_sent@)
     * - integer $issue_id The ID of the issue. If false, email will not be associated with issue.
     * - string $type The type of message this is.
     * - integer $sender_usr_id The id of the user sending this email.
     * - integer $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     */
    public static function queue(MailBuilder $builder, $to, $options = [])
    {
        $message = $builder->getMessage();
        $headers = $message->getHeaders();

        if (!$headers->has('from')) {
            $from = isset($options['from']) ? $options['from'] : Setup::get()->smtp->from;
            $message->setFrom($from);
        }

        self::addMail($builder->toMailMessage(), $to, $options);
    }

    /**
     * Adds an email to the outgoing mail queue.
     *
     * @param MailMessage $mail The Mail object
     * @param string $recipient The recipient, can be E-Mail header form ("User <email@example.org>")
     * @param array $options Optional options:
     * - integer $save_email_copy Whether to send a copy of this email to a configurable address or not (eventum_sent@)
     * - integer $issue_id The ID of the issue. If false, email will not be associated with issue.
     * - string $type The type of message this is.
     * - integer $sender_usr_id The id of the user sending this email.
     * - integer $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     * @return bool true if entry was added to mail queue table
     */
    public static function addMail(MailMessage $mail, $recipient, array $options = [])
    {
        $save_email_copy = isset($options['save_email_copy']) ? $options['save_email_copy'] : 0;
        $issue_id = isset($options['issue_id']) ? $options['issue_id'] : false;
        $type = isset($options['type']) ? $options['type'] : '';
        $sender_usr_id = isset($options['sender_usr_id']) ? $options['sender_usr_id'] : null;
        $type_id = isset($options['type_id']) ? $options['type_id'] : false;

        $prj_id = Auth::getCurrentProject(false);
        Workflow::modifyMailQueue($prj_id, $recipient, $mail, $issue_id, $type, $sender_usr_id, $type_id);

        // avoid sending emails out to users with inactive status
        $recipient_email = Mail_Helper::getEmailAddress($recipient);
        $usr_id = User::getUserIDByEmail($recipient_email);
        if ($usr_id) {
            $user_status = User::getStatusByEmail($recipient_email);
            // if user is not set to an active status, then silently ignore
            if (!User::isActiveStatus($user_status) && !User::isPendingStatus($user_status)) {
                return false;
            }
        }

        $recipient = Mail_Helper::fixAddressQuoting($recipient);

        $reminder_addresses = Reminder::_getReminderAlertAddresses();
        $headers = [];

        $role_id = User::getRoleByUser($usr_id, Issue::getProjectID($issue_id));
        $is_reminder_address = in_array(Mail_Helper::getEmailAddress($recipient), $reminder_addresses);
        if ($issue_id && ($usr_id && $role_id != User::ROLE_CUSTOMER) || $is_reminder_address) {
            $headers += Mail_Helper::getSpecializedHeaders($issue_id, $type);
        }

        // try to prevent triggering absence auto responders
        $headers['precedence'] = 'bulk'; // the 'classic' way, works with e.g. the unix 'vacation' tool
        $headers['Auto-submitted'] = 'auto-generated'; // the RFC 3834 way

        // if the Date: header is missing, add it.
        // FIXME: do in class? or add setDate() method?
        if (!$mail->getHeaders()->has('Date')) {
            $headers['Date'] = date('D, j M Y H:i:s O');
        }

        $mail->addHeaders($headers);

        $params = [
            'maq_save_copy' => $save_email_copy,
            'maq_queued_date' => Date_Helper::getCurrentDateGMT(),
            'maq_sender_ip_address' => !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            'maq_recipient' => Mime_Helper::decodeAddress($recipient),
            'maq_headers' => $mail->getHeaders()->toString(),
            'maq_body' => $mail->getContent(),
            'maq_iss_id' => $issue_id ?: null,
            'maq_subject' => $mail->subject,
            'maq_type' => $type,
        ];

        if ($sender_usr_id) {
            $params['maq_usr_id'] = $sender_usr_id;
        }
        if ($type_id) {
            $params['maq_type_id'] = $type_id;
        }

        $stmt = 'INSERT INTO {{%mail_queue}} SET ' . DB_Helper::buildSet($params);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Sends the queued up messages to their destinations. This can either try
     * to send emails that could not be sent before (status = 'error'), or just
     * emails just recently queued (status = 'pending').
     *
     * @param   string $status The status of the messages that need to be sent
     * @param   int $limit The limit of emails that we should send at one time
     * @param   bool $merge whether or not to send one merged email for multiple entries with the same status and type
     */
    public static function send($status, $limit = null, $merge = false)
    {
        if ($merge !== false) {
            // TODO: handle self::MAX_RETRIES, but that should be done per queue item
            foreach (self::_getMergedList($status, $limit) as $maq_ids) {
                $entries = self::_getEntries($maq_ids);

                $addresslist = new AddressList();
                foreach ($entries as $entry) {
                    $recipient = $entry['recipient'];
                    if (Mime_Helper::is8bit($recipient)) {
                        $recipient = Mime_Helper::encode($recipient);
                    }

                    $addresslist->addFromString($recipient);
                }

                $entry = $entries[0];
                $mail = MailMessage::createFromHeaderBody($entry['headers'], $entry['body']);
                $mail->setTo($addresslist);

                $e = self::_sendEmail($mail->to, $mail);

                if ($e instanceof Exception) {
                    $maq_id = implode(',', $maq_ids);
                    $details = $e->getMessage();
                    echo "Mail_Queue: issue #{$entry['maq_iss_id']}: Can't send merged mail $maq_id: $details\n";

                    foreach ($entries as $entry) {
                        self::_saveStatusLog($entry['id'], 'error', $details);
                    }

                    continue;
                }

                foreach ($entries as $entry) {
                    self::_saveStatusLog($entry['id'], 'sent', '');

                    if ($entry['save_copy']) {
                        $mail = MailMessage::createFromHeaderBody($entry['headers'], $entry['body']);
                        Mail_Helper::saveOutgoingEmailCopy($mail, $entry['maq_iss_id'], $entry['maq_type']);
                    }
                }
            }
            // FIXME: should not process the list again?
            //return;
        }

        foreach (self::_getList($status, $limit) as $maq_id) {
            $errors = self::getQueueErrorCount($maq_id);
            if ($errors > self::MAX_RETRIES) {
                // TODO: mark as status 'failed'
                continue;
            }

            $entry = self::_getEntry($maq_id);

            $mail = MailMessage::createFromHeaderBody($entry['headers'], $entry['body']);
            $e = self::_sendEmail($entry['recipient'], $mail);

            if ($e instanceof Exception) {
                $details = $e->getMessage();
                echo "Mail_Queue: issue #{$entry['maq_iss_id']}: Can't send mail $maq_id (retry $errors): $details\n";
                self::_saveStatusLog($entry['id'], 'error', $details);
                continue;
            }

            self::_saveStatusLog($entry['id'], 'sent', '');
            if ($entry['save_copy']) {
                Mail_Helper::saveOutgoingEmailCopy($mail, $entry['maq_iss_id'], $entry['maq_type']);
            }
        }
    }

    /**
     * Connects to the SMTP server and sends the queued message.
     *
     * @param string $recipient The recipient of this message
     * @param MailMessage $mail
     * @return true or a Exception object
     */
    private static function _sendEmail($recipient, MailMessage $mail)
    {
        $headers = $mail->getHeaders();

        // remove any Reply-To:/Return-Path: values from outgoing messages
        $headers->removeHeader('Reply-To');
        $headers->removeHeader('Return-Path');

        $transport = new MailTransport();

        // TODO: mail::send wants just bare addresses, do that ourselves
        $recipient = Mime_Helper::encodeAddress($recipient);

        return $transport->send($recipient, $mail);
    }

    /**
     * Retrieves the list of queued email messages ids, given a status.
     *
     * @param   string $status The status of the messages
     * @param   int $limit The limit on the number of messages that need to be returned
     * @return  array The list of queued email messages
     */
    private static function _getList($status, $limit)
    {
        $limit = (int) $limit;
        $sql = "SELECT
                    maq_id id
                 FROM
                    {{%mail_queue}}
                 WHERE
                    maq_status=?
                 ORDER BY
                    maq_id ASC
                 LIMIT
                    $limit OFFSET 0";
        try {
            $res = DB_Helper::getInstance()->getColumn($sql, [$status]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Retrieves the list of queued email messages ids, given a status, merged together by type
     *
     * @param   string $status The status of the messages
     * @param   int $limit The limit on the number of messages that need to be returned
     * @return  array The list of queued email messages
     */
    private static function _getMergedList($status, $limit = null)
    {
        $sql = 'SELECT
                    GROUP_CONCAT(maq_id) ids
                 FROM
                    {{%mail_queue}}
                 WHERE
                    maq_status=?
                 AND
                    maq_type_id > 0
                 GROUP BY
                    maq_type_id
                 ORDER BY
                    MIN(maq_id) ASC';

        $limit = (int) $limit;
        if ($limit) {
            $sql .= " LIMIT 0, $limit";
        }

        try {
            $res = DB_Helper::getInstance()->getAll($sql, [$status]);
        } catch (DatabaseException $e) {
            return [];
        }

        foreach ($res as &$value) {
            $value = explode(',', $value['ids']);
        }

        return $res;
    }

    /**
     * Retrieves queued email by maq_id.
     *
     * @param   int $maq_id ID of queue entry
     * @return  array The queued email message
     */
    private static function _getEntry($maq_id)
    {
        $stmt = 'SELECT
                    maq_id id,
                    maq_iss_id,
                    maq_save_copy save_copy,
                    maq_recipient recipient,
                    maq_headers headers,
                    maq_body body,
                    maq_type,
                    maq_usr_id
                 FROM
                    {{%mail_queue}}
                 WHERE
                    maq_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$maq_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Retrieves queued email by maq_ids.
     *
     * @param   array $maq_ids IDs of queue entries
     * @return  array The queued email message
     */
    private static function _getEntries($maq_ids)
    {
        $stmt = 'SELECT
                    maq_id id,
                    maq_iss_id,
                    maq_save_copy save_copy,
                    maq_recipient recipient,
                    maq_headers headers,
                    maq_body body,
                    maq_type,
                    maq_usr_id
                 FROM
                    {{%mail_queue}}
                 WHERE
                    maq_id IN (' . implode(',', $maq_ids) . ')';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Return number of errors for this queue item
     *
     * @param int $maq_id
     * @return int
     */
    private static function getQueueErrorCount($maq_id)
    {
        $sql = 'select count(*) from {{%mail_queue_log}} where mql_maq_id=? and mql_status=?';
        $res = DB_Helper::getInstance()->getOne($sql, [$maq_id, 'error']);

        return (int) $res;
    }

    /**
     * Saves a log entry about the attempt, successful or otherwise, to send the
     * queued email message. Also updates maq_status of $maq_id to $status.
     *
     * @param   int $maq_id The queued email message ID
     * @param   string $status The status of the attempt ('sent' or 'error')
     * @param   string $server_message The full message from the SMTP server, in case of an error
     * @return  bool
     */
    private static function _saveStatusLog($maq_id, $status, $server_message)
    {
        $stmt = 'INSERT INTO
                    {{%mail_queue_log}}
                 (
                    mql_maq_id,
                    mql_created_date,
                    mql_status,
                    mql_server_message
                 ) VALUES (
                    ?, ?, ?, ?
                 )';
        $params = [
            $maq_id,
            Date_Helper::getCurrentDateGMT(),
            $status,
            $server_message,
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = 'UPDATE
                    {{%mail_queue}}
                 SET
                    maq_status=?
                 WHERE
                    maq_id=?';

        DB_Helper::getInstance()->query($stmt, [$status, $maq_id]);

        return true;
    }

    /**
     * Returns the mail queue for a specific issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array An array of emails from the queue
     */
    public static function getListByIssueID($issue_id)
    {
        $stmt = 'SELECT
                    maq_id,
                    maq_queued_date,
                    maq_status,
                    maq_recipient,
                    maq_subject
                 FROM
                    {{%mail_queue}}
                 WHERE
                    maq_iss_id = ?
                 ORDER BY
                    maq_queued_date ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res;
    }

    /**
     * Returns the mail queue entry based on ID.
     *
     * @acess   public
     * @param   int $maq_id the id of the mail queue entry
     * @return  array An array of information
     */
    public static function getEntry($maq_id)
    {
        $stmt = 'SELECT
                    maq_iss_id,
                    maq_queued_date,
                    maq_status,
                    maq_recipient,
                    maq_subject,
                    maq_headers,
                    maq_body
                 FROM
                    {{%mail_queue}}
                 WHERE
                    maq_id = ?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$maq_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res;
    }

    /**
     * @param int $type_id
     */
    /**
     * @param string[]|string $types
     * @param int $type_id
     * @return array|bool
     */
    public static function getMessageRecipients($types, $type_id)
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        $types_list = DB_Helper::buildList($types);
        $sql = "SELECT
                    maq_recipient
                FROM
                    {{%mail_queue}}
                WHERE
                    maq_type IN ($types_list) AND
                    maq_type_id = ?";
        $params = $types;
        $params[] = $type_id;
        try {
            $res = DB_Helper::getInstance()->getColumn($sql, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        foreach ($res as &$row) {
            // FIXME: what does quote stripping fix here
            $row = Mime_Helper::decodeAddress(str_replace('"', '', $row));
        }

        return $res;
    }

    /**
     * Truncates the maq_body field of any emails older then one month.
     *
     * @return bool
     */
    public static function truncate()
    {
        $sql = "UPDATE
                    {{%mail_queue}}
                SET
                  maq_body = '',
                  maq_status = 'truncated'
                WHERE
                    maq_status = 'sent' AND
                    maq_queued_date <= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        try {
            DB_Helper::getInstance()->query($sql);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }
}
