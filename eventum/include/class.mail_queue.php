<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id$
//


include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_PEAR_PATH . 'Mail.php');

class Mail_Queue
{
    /**
     * Returns the full path to the file that keeps the process ID of the
     * running script.
     *
     * @access  private
     * @return  string The full path of the process file
     */
    function _getProcessFilename()
    {
        return APP_PATH . 'misc/process_mail_queue.pid';
    }


    /**
     * Checks whether it is safe or not to run the mail queue script.
     *
     * @access  public
     * @return  boolean
     */
    function isSafeToRun()
    {
        $pid = Mail_Queue::getProcessID();
        if (!empty($pid)) {
            return false;
        } else {
            // create the pid file
            $fp = fopen(Mail_Queue::_getProcessFilename(), 'w');
            fwrite($fp, getmypid());
            fclose($fp);
            return true;
        }
    }


    /**
     * Returns the process ID of the script, if any.
     *
     * @access  public
     * @return  integer The process ID of the script
     */
    function getProcessID()
    {
        static $pid;

        if (!empty($pid)) {
            return $pid;
        }

        $pid_file = Mail_Queue::_getProcessFilename();
        if (!file_exists($pid_file)) {
            return 0;
        } else {
            $pid = trim(implode('', file($pid_file)));
            return $pid;
        }
    }


    /**
     * Removes the process file to allow other instances of this script to run.
     *
     * @access  public
     * @return  void
     */
    function removeProcessFile()
    {
        @unlink(Mail_Queue::_getProcessFilename());
    }


    /**
     * Adds an email to the outgoing mail queue.
     *
     * @access  public
     * @param   string $recipient The recipient of this email
     * @param   array $headers The list of headers that should be sent with this email
     * @param   string $body The body of the message
     * @param   integer $save_email_copy Whether to send a copy of this email to a configurable address or not (eventum_sent@)
     * @return  true, or a PEAR_Error object
     */
    function add($recipient, $headers, $body, $save_email_copy = 0)
    {
        list(,$text_headers) = Mail::prepareHeaders($headers);

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue
                 (
                    maq_save_copy,
                    maq_queued_date,
                    maq_sender_ip_address,
                    maq_recipient,
                    maq_headers,
                    maq_body
                 ) VALUES (
                    $save_email_copy,
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . getenv("REMOTE_ADDR") . "',
                    '" . Misc::escapeString($recipient) . "',
                    '" . Misc::escapeString($text_headers) . "',
                    '" . Misc::escapeString($body) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return $res;
        } else {
            return true;
        }
    }


    /**
     * Sends the queued up messages to their destinations. This can either try 
     * to send emails that couldn't be sent before (status = 'error'), or just 
     * emails just recently queued (status = 'pending').
     *
     * @access  public
     * @param   string $status The status of the messages that need to be sent
     * @param   integer $limit The limit of emails that we should send at one time
     */
    function send($status, $limit)
    {
        // get list of emails to send
        $emails = Mail_Queue::_getList($status, $limit);
        // foreach email
        for ($i = 0; $i < count($emails); $i++) {
            $result = Mail_Queue::_sendEmail($emails[$i]['recipient'], $emails[$i]['headers'], $emails[$i]['body']);
            if (PEAR::isError($result)) {
                Mail_Queue::_saveLog($emails[$i]['id'], 'error', Mail_Queue::_getErrorMessage($result));
            } else {
                Mail_Queue::_saveLog($emails[$i]['id'], 'sent', '');
                if ($emails[$i]['save_copy']) {
                    // send a copy of this email to eventum_sent@
                    Mail_API::saveEmailInformation($emails[$i]['headers'], $emails[$i]['body']);
                }
            }
        }
    }


    /**
     * Connects to the SMTP server and sends the queued message.
     *
     * @access  private
     * @param   string $recipient The recipient of this message
     * @param   string $text_headers The full headers of this message
     * @param   string $body The full body of this message
     * @return  true, or a PEAR_Error object
     */
    function _sendEmail($recipient, $text_headers, $body)
    {
        $header_names = Mime_Helper::getHeaderNames($text_headers);
        $_headers = Mail_Queue::_getHeaders($text_headers, $body);
        $headers = array();
        foreach ($_headers as $lowercase_name => $value) {
            $headers[$header_names[$lowercase_name]] = Mime_Helper::encode($value);
        }
        // mutt sucks, so let's remove the broken Mime-Version header and add the proper one
        if (in_array('Mime-Version', array_keys($headers))) {
            unset($headers['Mime-Version']);
            $headers['MIME-Version'] = '1.0';
        }
        $mail =& Mail::factory('smtp', Mail_Queue::_getSMTPSettings());
        $res = $mail->send($recipient, $headers, $body);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return $res;
        } else {
            return true;
        }
    }


    /**
     * Parses the full email message and returns an array of the headers 
     * contained in it.
     *
     * @access  private
     * @param   string $text_headers The full headers of this message
     * @param   string $body The full body of this message
     * @return  array The list of headers
     */
    function _getHeaders($text_headers, $body)
    {
        $structure = Mime_Helper::decode($text_headers . "\n\n" . $body, FALSE, FALSE);
        return $structure->headers;
    }


    /**
     * Retrieves the list of queued email messages, given a status.
     *
     * @access  private
     * @param   string $status The status of the messages
     * @param   integer $limit The limit on the number of messages that need to be returned
     * @return  array The list of queued email messages
     */
    function _getList($status, $limit = 50)
    {
        $stmt = "SELECT
                    maq_id id,
                    maq_save_copy save_copy,
                    maq_recipient recipient,
                    maq_headers headers,
                    maq_body body
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue
                 WHERE
                    maq_status='$status'
                 ORDER BY
                    maq_id ASC
                 LIMIT
                    0, $limit";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Saves a log entry about the attempt, successful or otherwise, to send the
     * queued email message.
     *
     * @access  private
     * @param   integer $maq_id The queued email message ID
     * @param   string $status The status of the attempt ('sent' or 'error')
     * @param   string $server_message The full message from the SMTP server, in case of an error
     * @return  boolean
     */
    function _saveLog($maq_id, $status, $server_message)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue_log
                 (
                    mql_maq_id,
                    mql_created_date,
                    mql_status,
                    mql_server_message
                 ) VALUES (
                    $maq_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    '$status',
                    '$server_message'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue
                     SET
                        maq_status='$status'
                     WHERE
                        maq_id=$maq_id";
            $GLOBALS["db_api"]->dbh->query($stmt);
            return true;
        }
    }


    /**
     * Handles the PEAR_Error object returned from the SMTP server, and returns
     * an appropriate error message string.
     *
     * @access  private
     * @param   object $error The PEAR_Error object
     * @return  string The error message
     */
    function _getErrorMessage($error)
    {
        return $error->getMessage() . "/" . $error->getDebugInfo();
    }


    /**
     * Returns the configuration parameters for the SMTP server that should
     * be used for outgoing email messages.
     *
     * @access  private
     * @return  array The SMTP related configuration parameters
     */
    function _getSMTPSettings()
    {
        $settings = Setup::load();
        return $settings["smtp"];
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Mail_Queue Class');
}
?>