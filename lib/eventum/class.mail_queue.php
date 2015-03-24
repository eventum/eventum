<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

class Mail_Queue
{
    /**
     * Adds an email to the outgoing mail queue.
     *
     * @param   string $recipient The recipient of this email
     * @param   array $headers The list of headers that should be sent with this email
     * @param   string $body The body of the message
     * @param   integer $save_email_copy Whether to send a copy of this email to a configurable address or not (eventum_sent@)
     * @param   integer $issue_id The ID of the issue. If false, email will not be associated with issue.
     * @param   string $type The type of message this is.
     * @param   integer $sender_usr_id The id of the user sending this email.
     * @param   integer $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     * @return  true, or a PEAR_Error object
     */
    public static function add($recipient, $headers, $body, $save_email_copy = 0, $issue_id = false, $type = '', $sender_usr_id = false, $type_id = false)
    {
        Workflow::modifyMailQueue(Auth::getCurrentProject(false), $recipient, $headers, $body, $issue_id, $type, $sender_usr_id, $type_id);

        // avoid sending emails out to users with inactive status
        $recipient_email = Mail_Helper::getEmailAddress($recipient);
        $usr_id = User::getUserIDByEmail($recipient_email);
        if (!empty($usr_id)) {
            $user_status = User::getStatusByEmail($recipient_email);
            // if user is not set to an active status, then silently ignore
            if ((!User::isActiveStatus($user_status)) && (!User::isPendingStatus($user_status))) {
                return false;
            }
        }

        $to_usr_id = User::getUserIDByEmail($recipient_email);
        $recipient = Mail_Helper::fixAddressQuoting($recipient);

        $reminder_addresses = Reminder::_getReminderAlertAddresses();

        // add specialized headers
        if ((!empty($issue_id)) && ((!empty($to_usr_id)) && (User::getRoleByUser($to_usr_id, Issue::getProjectID($issue_id)) != User::getRoleID("Customer"))) ||
                (@in_array(Mail_Helper::getEmailAddress($recipient), $reminder_addresses))) {
            $headers += Mail_Helper::getSpecializedHeaders($issue_id, $type, $headers, $sender_usr_id);
        }

        // try to prevent triggering absence auto responders
        $headers['precedence'] = 'bulk'; // the 'classic' way, works with e.g. the unix 'vacation' tool
        $headers['Auto-submitted'] = 'auto-generated'; // the RFC 3834 way

        if (empty($issue_id)) {
            $issue_id = 'null';
        }
        // if the Date: header is missing, add it.
        if (empty($headers['Date'])) {
            $headers['Date'] = Mime_Helper::encode(date('D, j M Y H:i:s O'));
        }
        if (!empty($headers['To'])) {
            $headers['To'] = Mail_Helper::fixAddressQuoting($headers['To']);
        }
        // encode headers and add special mime headers
        $headers = Mime_Helper::encodeHeaders($headers);

        $res = Mail_Helper::prepareHeaders($headers);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);

            return $res;
        }

        // convert array of headers into text headers
        list(,$text_headers) = $res;

        $params = array(
            'maq_save_copy' => $save_email_copy,
            'maq_queued_date' => Date_Helper::getCurrentDateGMT(),
            'maq_sender_ip_address' => !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            'maq_recipient' => $recipient,
            'maq_headers' => $text_headers,
            'maq_body' => $body,
            'maq_iss_id' => $issue_id,
            'maq_subject' => $headers["Subject"],
            'maq_type' => $type,
        );

        if ($sender_usr_id) {
            $params['maq_usr_id'] = $sender_usr_id;
        }
        if ($type_id) {
            $params['maq_type_id'] = $type_id;
        }

        $stmt = "INSERT INTO {{%mail_queue}} SET ".DB_Helper::buildSet($params);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return $res;
        }

        return true;
    }

    /**
     * Sends the queued up messages to their destinations. This can either try
     * to send emails that couldn't be sent before (status = 'error'), or just
     * emails just recently queued (status = 'pending').
     *
     * @param   string $status The status of the messages that need to be sent
     * @param   integer $limit The limit of emails that we should send at one time
     * @param   boolean $merge Whether or not to send one merged email for multiple entries with the same status and type.
     */
    public static function send($status, $limit = false, $merge = false)
    {
        if ($merge !== false) {
            foreach (self::_getMergedList($status, $limit) as $maq_ids) {
                $emails = self::_getEntries($maq_ids);
                $recipients = array();

                foreach ($emails as $email) {
                    $recipients[] = $email['recipient'];
                }

                $email = $emails[0];
                $recipients = implode(',', $recipients);
                $message = $email['headers'] . "\r\n\r\n" . $email['body'];
                $structure = Mime_Helper::decode($message, false, false);
                $headers = $structure->headers;

                $headers['to'] = $recipients;
                $headers = Mime_Helper::encodeHeaders($headers);

                $res = Mail_Helper::prepareHeaders($headers);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);

                    return $res;
                }

                list(,$text_headers) = $res;
                $result = self::_sendEmail($recipients, $text_headers, $email['body'], $status);

                if (PEAR::isError($result)) {
                    $maq_id = implode(',', $maq_ids);
                    $details = $result->getMessage() . "/" . $result->getDebugInfo();
                    echo "Mail_Queue: issue #{$email['maq_iss_id']}: Can't send merged mail $maq_id: $details\n";

                    foreach ($emails as $email) {
                        self::_saveStatusLog($email['id'], 'error', $details);
                    }

                    continue;
                }

                foreach ($emails as $email) {
                    self::_saveStatusLog($email['id'], 'sent', '');

                    if ($email['save_copy']) {
                        Mail_Helper::saveOutgoingEmailCopy($email);
                    }
                }
            }
        }

        foreach (self::_getList($status, $limit) as $maq_id) {
            $email = self::_getEntry($maq_id);
            $result = self::_sendEmail($email['recipient'], $email['headers'], $email['body'], $status);

            if (PEAR::isError($result)) {
                $details = $result->getMessage() . "/" . $result->getDebugInfo();
                echo "Mail_Queue: issue #{$email['maq_iss_id']}: Can't send mail $maq_id: $details\n";
                self::_saveStatusLog($email['id'], 'error', $details);
                continue;
            }

            self::_saveStatusLog($email['id'], 'sent', '');
            if ($email['save_copy']) {
                Mail_Helper::saveOutgoingEmailCopy($email);
            }
        }
    }

    /**
     * Connects to the SMTP server and sends the queued message.
     *
     * @param   string $recipient The recipient of this message
     * @param   string $text_headers The full headers of this message
     * @param   string $body The full body of this message
     * @param   string $status The status of this message
     * @return  true, or a PEAR_Error object
     */
    private function _sendEmail($recipient, $text_headers, &$body, $status)
    {
        $header_names = Mime_Helper::getHeaderNames($text_headers);
        $_headers = self::_getHeaders($text_headers, $body);
        $headers = array();
        foreach ($_headers as $lowercase_name => $value) {
            // need to remove the quotes to avoid a parsing problem
            // on senders that have extended characters in the first
            // or last words in their sender name
            if ($lowercase_name == 'from') {
                $value = Mime_Helper::removeQuotes($value);
            }
            $value = Mime_Helper::encode($value);
            // add the quotes back
            if ($lowercase_name == 'from') {
                $value = Mime_Helper::quoteSender($value);
            }
            $headers[$header_names[$lowercase_name]] = $value;
        }

        // remove any Reply-To:/Return-Path: values from outgoing messages
        unset($headers['Reply-To']);
        unset($headers['Return-Path']);

        // mutt sucks, so let's remove the broken Mime-Version header and add the proper one
        if (in_array('Mime-Version', array_keys($headers))) {
            unset($headers['Mime-Version']);
            $headers['MIME-Version'] = '1.0';
        }

        $mail = Mail::factory('smtp', Mail_Helper::getSMTPSettings());
        $res = $mail->send($recipient, $headers, $body);
        if (PEAR::isError($res)) {
            // special handling of errors when the mail server is down
            $msg = $res->getMessage();
            $cant_notify = ($status == 'error' || strstr($msg , 'unable to connect to smtp server') || stristr($msg, 'Failed to connect to') !== false);
            Error_Handler::logError(array($msg, $res->getDebugInfo()), __FILE__, __LINE__, !$cant_notify);

            return $res;
        }

        return true;
    }

    /**
     * Parses the full email message and returns an array of the headers
     * contained in it.
     *
     * @param   string $text_headers The full headers of this message
     * @param   string $body The full body of this message
     * @return  array The list of headers
     */
    private function _getHeaders($text_headers, &$body)
    {
        $message = $text_headers . "\n\n" . $body;
        $structure = Mime_Helper::decode($message, false, false);

        return $structure->headers;
    }

    /**
     * Retrieves the list of queued email messages ids, given a status.
     *
     * @param   string $status The status of the messages
     * @param   integer $limit The limit on the number of messages that need to be returned
     * @return  array The list of queued email messages
     */
    private function _getList($status, $limit)
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
            $res = DB_Helper::getInstance()->getColumn($sql, array($status));
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Retrieves the list of queued email messages ids, given a status, merged together by type
     *
     * @param   string $status The status of the messages
     * @param   integer $limit The limit on the number of messages that need to be returned
     * @return  array The list of queued email messages
     */
    private function _getMergedList($status, $limit = null)
    {
        $sql = "SELECT
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
                    MIN(maq_id) ASC";

        $limit = (int) $limit;
        if ($limit) {
            $sql .= " LIMIT 0, $limit";
        }

        try {
            $res = DB_Helper::getInstance()->getAll($sql, array($status));
        } catch (DbException $e) {
            return array();
        }

        foreach ($res as &$value) {
           $value = explode(',', $value['ids']);
        }

        return $res;
    }

    /**
     * Retrieves queued email by maq_id.
     *
     * @param   integer $maq_id ID of queue entry
     * @return  array The queued email message
     */
    private function _getEntry($maq_id)
    {
        $stmt = "SELECT
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
                    maq_id=?";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($maq_id));
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Retrieves queued email by maq_ids.
     *
     * @param   array $maq_ids IDs of queue entries
     * @return  array The queued email message
     */
    private function _getEntries($maq_ids)
    {
        $stmt = "SELECT
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
                    maq_id IN (" . implode(',', $maq_ids) . ")";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Saves a log entry about the attempt, successful or otherwise, to send the
     * queued email message. Also updates maq_status of $maq_id to $status.
     *
     * @param   integer $maq_id The queued email message ID
     * @param   string $status The status of the attempt ('sent' or 'error')
     * @param   string $server_message The full message from the SMTP server, in case of an error
     * @return  boolean
     */
    private function _saveStatusLog($maq_id, $status, $server_message)
    {
        $stmt = "INSERT INTO
                    {{%mail_queue_log}}
                 (
                    mql_maq_id,
                    mql_created_date,
                    mql_status,
                    mql_server_message
                 ) VALUES (
                    ?, ?, ?, ?
                 )";
        $params = array(
            $maq_id,
            Date_Helper::getCurrentDateGMT(),
            $status,
            $server_message,
        );
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return false;
        }

        $stmt = "UPDATE
                    {{%mail_queue}}
                 SET
                    maq_status=?
                 WHERE
                    maq_id=?";

        DB_Helper::getInstance()->query($stmt, array($status, $maq_id));

        return true;
    }

    /**
     * Returns the mail queue for a specific issue.
     *
     * @param   integer $issue_id The issue ID
     * @return  array An array of emails from the queue
     */
    public static function getListByIssueID($issue_id)
    {
        $stmt = "SELECT
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
                    maq_queued_date ASC";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($issue_id));
        } catch (DbException $e) {
            return false;
        }

        if (count($res) > 0) {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['maq_recipient'] = Mime_Helper::decodeAddress($res[$i]['maq_recipient']);
                $res[$i]['maq_queued_date'] = Date_Helper::getFormattedDate(Date_Helper::getUnixTimestamp($res[$i]['maq_queued_date'], 'GMT'));
                $res[$i]['maq_subject'] = Mime_Helper::fixEncoding($res[$i]['maq_subject']);
            }
        }

        return $res;
    }

    /**
     * Returns the mail queue entry based on ID.
     *
     * @acess   public
     * @param   integer $maq_id The id of the mail queue entry.
     * @return  array An array of information
     */
    public static function getEntry($maq_id)
    {
        $stmt = "SELECT
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
                    maq_id = ?";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($maq_id));
        } catch (DbException $e) {
            return false;
        }

        return $res;
    }

    public static function getMessageRecipients($types, $type_id)
    {
        if (!is_array($types)) {
            $types = array($types);
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
        } catch (DbException $e) {
            return false;
        }

        for ($i = 0; $i < count($res); $i++) {
            // FIXME: what does quote stripping fix here
            $res[$i] = Mime_Helper::decodeAddress(str_replace('"', '', $res[$i]));
        }

        return $res;
    }
}
