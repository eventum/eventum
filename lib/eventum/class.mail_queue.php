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

use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\Mail\MailBuilder;
use Eventum\Mail\MailMessage;
use Eventum\Mail\MailTransport;
use Symfony\Component\EventDispatcher\GenericEvent;

class Mail_Queue
{
    /**
     * Number of times to retry 'error' status mails before giving up.
     */
    const MAX_RETRIES = 20;

    const STATUS_PENDING = 'pending';
    const STATUS_ERROR = 'error';
    const STATUS_FAILED = 'failed';
    const STATUS_SENT = 'sent';
    const STATUS_TRUNCATED = 'truncated';

    /**
     * Adds an email to the outgoing mail queue.
     *
     * @param MailBuilder|MailMessage $mail
     * @param string $recipient The recipient, can be E-Mail header form ("User <email@example.org>")
     * @param array $options Optional options:
     * - string $from From address, defaults to system user
     * - integer $save_email_copy Whether to send a copy of this email to a configurable address or not (eventum_sent@)
     * - integer $issue_id The ID of the issue. If false, email will not be associated with issue.
     * - string $type The type of message this is.
     * - integer $sender_usr_id The id of the user sending this email.
     * - integer $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     * @return bool true if entry was added to mail queue table
     */
    public static function queue($mail, $recipient, array $options = [])
    {
        $prj_id = Auth::getCurrentProject(false);

        $save_email_copy = isset($options['save_email_copy']) ? $options['save_email_copy'] : 0;
        $issue_id = isset($options['issue_id']) ? $options['issue_id'] : false;
        $type = isset($options['type']) ? $options['type'] : '';
        $type_id = isset($options['type_id']) ? $options['type_id'] : false;
        $sender_usr_id = isset($options['sender_usr_id']) ? $options['sender_usr_id'] : null;

        if ($mail instanceof MailBuilder) {
            $mail = $mail->toMailMessage();
        }
        $headers = $mail->getHeaders();

        if (!$mail->from) {
            $from = isset($options['from']) ? $options['from'] : Setup::get()->smtp->from;
            $mail->setFrom($from);
        }

        Workflow::modifyMailQueue($prj_id, $recipient, $mail, $options);

        // avoid sending emails out to users with inactive status
        // TODO: use EventDispatcher to handle this
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

        if ($issue_id) {
            $role_id = User::getRoleByUser($usr_id, Issue::getProjectID($issue_id));
            $is_reminder_address = in_array(Mail_Helper::getEmailAddress($recipient), $reminder_addresses);
            if (($usr_id && $role_id != User::ROLE_CUSTOMER) || $is_reminder_address) {
                Mail_Helper::addSpecializedHeaders($mail, $issue_id, $type);
            }
        }

        // try to prevent triggering absence auto responders
        $mail->addHeaders([
            // the 'classic' way, works with e.g. the unix 'vacation' tool
            'precedence' => 'bulk',
            // the RFC 3834 way
            'Auto-submitted' => 'auto-generated',
        ]);

        // if the Date: header is missing, add it.
        if (!$headers->has('Date')) {
            $mail->setDate();
        }

        $params = [
            'maq_save_copy' => $save_email_copy,
            'maq_queued_date' => Date_Helper::getCurrentDateGMT(),
            'maq_sender_ip_address' => !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            'maq_recipient' => Mime_Helper::decodeAddress($recipient),
            'maq_headers' => $mail->getHeaders()->toString(),
            'maq_body' => $mail->getContent(),
            'maq_iss_id' => $issue_id ?: null,
            'maq_subject' => $mail->subject,
            'maq_message_id' => $mail->messageId,
            'maq_type' => $type,
        ];

        if ($sender_usr_id) {
            $params['maq_usr_id'] = $sender_usr_id;
        }
        if ($type_id) {
            $params['maq_type_id'] = $type_id;
        }

        $stmt = 'INSERT INTO `mail_queue` SET ' . DB_Helper::buildSet($params);
        DB_Helper::getInstance()->query($stmt, $params);

        return true;
    }

    /**
     * Sends the queued up messages to their destinations. This can either try
     * to send emails that could not be sent before (status = 'error'), or just
     * emails just recently queued (status = 'pending').
     *
     * @param   string $status The status of the messages that need to be sent
     * @param   int $limit The limit of emails that we should send at one time
     * @param   bool $merge whether or not to send one merged email for multiple entries with the same status and type. Functionality DROPPED
     */
    public static function send($status, $limit = null, $merge = false)
    {
        if ($merge !== false) {
            throw new RuntimeException('Merged list no longer supported');
        }

        $dispatcher = EventManager::getEventDispatcher();
        foreach (self::getEntries($status, $limit) as $entry) {
            try {
                $mail = MailMessage::createFromHeaderBody($entry['headers'], $entry['body']);

                unset($entry['headers'], $entry['body']);
                $event = new GenericEvent($mail, $entry);
                $dispatcher->dispatch(SystemEvents::MAIL_QUEUE_SEND, $event);

                $transport = new MailTransport();
                $transport->send($entry['recipient'], $mail);

                $dispatcher->dispatch(SystemEvents::MAIL_QUEUE_SENT, $event);
            } catch (Exception $e) {
                $event = new GenericEvent($e, $entry);
                $dispatcher->dispatch(SystemEvents::MAIL_QUEUE_ERROR, $event);
            }
        }
    }

    /**
     * Retrieves the list of queued email messages, given a status.
     *
     * @param   string $status The status of the messages
     * @param   int $limit The limit on the number of messages that need to be returned
     * @return Generator
     */
    private static function getEntries($status, $limit)
    {
        $limit = (int)$limit;
        $sql = "SELECT
                    maq_id id
                 FROM
                    `mail_queue`
                 WHERE
                    maq_status=?
                 ORDER BY
                    maq_id ASC
                 LIMIT
                    $limit OFFSET 0";

        $items = DB_Helper::getInstance()->getColumn($sql, [$status]);

        foreach ($items as $maq_id) {
            // to avoid re-sending very old errored mails
            // add this backward compat block.
            // drop in 3.5.0 and convert to db migrations to set those as 'blocked'
            $sql = 'select count(*) from `mail_queue_log` where mql_maq_id=? and mql_status=?';
            $res = DB_Helper::getInstance()->getOne($sql, [$maq_id, self::STATUS_ERROR]);
            if ((int)$res > self::MAX_RETRIES) {
                continue;
            }

            yield self::_getEntry($maq_id);
        }
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
                    `mail_queue`
                 WHERE
                    maq_id=?';

        return DB_Helper::getInstance()->getRow($stmt, [$maq_id]);
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
                    `mail_queue`
                 WHERE
                    maq_iss_id = ?
                 ORDER BY
                    maq_queued_date ASC';

        return DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
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
                    `mail_queue`
                 WHERE
                    maq_id = ?';

        return DB_Helper::getInstance()->getRow($stmt, [$maq_id]);
    }

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
                    `mail_queue`
                WHERE
                    maq_type IN ($types_list) AND
                    maq_type_id = ?";
        $params = $types;
        $params[] = $type_id;
        $res = DB_Helper::getInstance()->getColumn($sql, $params);

        foreach ($res as &$row) {
            // FIXME: what does quote stripping fix here
            $row = Mime_Helper::decodeAddress(str_replace('"', '', $row));
        }

        return $res;
    }

    /**
     * Truncates the maq_body field of any emails older then one month.
     *
     * @param string $interval MySQL Interval definition
     */
    public static function truncate($interval)
    {
        $sql = "UPDATE
                    `mail_queue`
                SET
                  maq_body = '',
                  maq_status = ?
                WHERE
                    maq_status = ? AND
                    maq_queued_date <= DATE_SUB(NOW(), INTERVAL $interval)";
        DB_Helper::getInstance()->query($sql, [self::STATUS_TRUNCATED, self::STATUS_SENT]);
    }
}
