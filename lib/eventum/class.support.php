<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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


/**
 * Class to handle the business logic related to the email feature of
 * the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Support
{
    /**
     * Permanently removes the given support emails from the associated email
     * server.
     *
     * @access  public
     * @param   array $sup_ids The list of support emails
     * @return  integer 1 if the removal worked, -1 otherwise
     */
    function expungeEmails($sup_ids)
    {
        $accounts = array();

        $stmt = "SELECT
                    sup_id,
                    sup_message_id,
                    sup_ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id IN (" . implode(', ', Misc::escapeInteger($sup_ids)) . ")";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            for ($i = 0; $i < count($res); $i++) {
                // don't remove emails from the imap/pop3 server if the email
                // account is set to leave a copy of the messages on the server
                $account_details = Email_Account::getDetails($res[$i]['sup_ema_id']);
                if (!$account_details['leave_copy']) {
                    // try to re-use an open connection to the imap server
                    if (!in_array($res[$i]['sup_ema_id'], array_keys($accounts))) {
                        $accounts[$res[$i]['sup_ema_id']] = self::connectEmailServer(Email_Account::getDetails($res[$i]['sup_ema_id']));
                    }
                    $mbox = $accounts[$res[$i]['sup_ema_id']];
                    if ($mbox !== FALSE) {
                        // now try to find the UID of the current message-id
                        $matches = @imap_search($mbox, 'TEXT "' . $res[$i]['sup_message_id'] . '"');
                        if (count($matches) > 0) {
                            for ($y = 0; $y < count($matches); $y++) {
                                $headers = imap_headerinfo($mbox, $matches[$y]);
                                // if the current message also matches the message-id header, then remove it!
                                if ($headers->message_id == $res[$i]['sup_message_id']) {
                                    @imap_delete($mbox, $matches[$y]);
                                    @imap_expunge($mbox);
                                    break;
                                }
                            }
                        }
                    }
                }
                // remove the email record from the table
                self::removeEmail($res[$i]['sup_id']);
            }
            return 1;
        }
    }


    /**
     * Removes the given support email from the database table.
     *
     * @access  public
     * @param   integer $sup_id The support email ID
     * @return  boolean
     */
    function removeEmail($sup_id)
    {
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id=" . Misc::escapeInteger($sup_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                     WHERE
                        seb_sup_id=" . Misc::escapeInteger($sup_id);
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Method used to get the next and previous messages in order to build
     * side links when viewing a particular email.
     *
     * @access  public
     * @param   integer $sup_id The email ID
     * @return  array Information on the next and previous messages
     */
    function getListingSides($sup_id)
    {
        $options = self::saveSearchParams();

        $stmt = "SELECT
                    sup_id,
                    sup_ema_id
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                    )
                    LEFT JOIN
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    ON
                        sup_iss_id = iss_id";
        if (!empty($options['keywords'])) {
            $stmt .= "," . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body";
        }
        $stmt .= self::buildWhereClause($options);
        $stmt .= "
                 ORDER BY
                    " . $options["sort_by"] . " " . $options["sort_order"];
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // COMPAT: the next line requires PHP >= 4.0.5
            $email_ids = array_keys($res);
            $index = array_search($sup_id, $email_ids);
            if (!empty($email_ids[$index+1])) {
                $next = $email_ids[$index+1];
            }
            if (!empty($email_ids[$index-1])) {
                $previous = $email_ids[$index-1];
            }
            return array(
                "next"     => array(
                    'sup_id' => @$next,
                    'ema_id' => @$res[$next]
                ),
                "previous" => array(
                    'sup_id' => @$previous,
                    'ema_id' => @$res[$previous]
                )
            );
        }
    }


    /**
     * Method used to get the next and previous messages in order to build
     * side links when viewing a particular email associated with an issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $sup_id The email ID
     * @return  array Information on the next and previous messages
     */
    function getIssueSides($issue_id, $sup_id)
    {
        $stmt = "SELECT
                    sup_id,
                    sup_ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_iss_id=" . Misc::escapeInteger($issue_id) . "
                 ORDER BY
                    sup_id ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // COMPAT: the next line requires PHP >= 4.0.5
            $email_ids = array_keys($res);
            $index = array_search($sup_id, $email_ids);
            if (!empty($email_ids[$index+1])) {
                $next = $email_ids[$index+1];
            }
            if (!empty($email_ids[$index-1])) {
                $previous = $email_ids[$index-1];
            }
            return array(
                "next"     => array(
                    'sup_id' => @$next,
                    'ema_id' => @$res[$next]
                ),
                "previous" => array(
                    'sup_id' => @$previous,
                    'ema_id' => @$res[$previous]
                )
            );
        }
    }


    /**
     * Method used to save the email note into a backup directory.
     *
     * @access  public
     * @param   string $message The full body of the email
     */
    function saveRoutedEmail($message)
    {
        if (!defined('APP_ROUTED_MAILS_SAVEDIR') || !APP_ROUTED_MAILS_SAVEDIR) {
            return;
        }
        list($usec,) = explode(' ', microtime());
        $filename = date('Y-m-d_H-i-s_') . $usec . '.note.txt';
        $file = APP_ROUTED_MAILS_SAVEDIR . '/routed_emails/' . $filename;
        file_put_contents($file, $message);
        chmod($file, 0644);
    }


    /**
     * Method used to get the sender of a given set of emails.
     *
     * @access  public
     * @param   integer $sup_ids The email IDs
     * @return  array The 'From:' headers for those emails
     */
    function getSender($sup_ids)
    {
        $stmt = "SELECT
                    sup_from
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id IN (" . implode(", ", Misc::escapeInteger($sup_ids)) . ")";
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (empty($res)) {
                return array();
            } else {
                return $res;
            }
        }
    }


    /**
     * Method used to clear the error stack as required by the IMAP PHP extension.
     *
     * @access  public
     * @return  void
     */
    function clearErrors()
    {
        @imap_errors();
    }


    /**
     * Method used to restore the specified support emails from
     * 'removed' to 'active'.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function restoreEmails()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["item"]));
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_removed=0
                 WHERE
                    sup_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the list of support email entries that are
     * set as 'removed'.
     *
     * @access  public
     * @return  array The list of support emails
     */
    function getRemovedList()
    {
        $stmt = "SELECT
                    sup_id,
                    sup_date,
                    sup_subject,
                    sup_from
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_prj_id=" . Auth::getCurrentProject() . " AND
                    ema_id=sup_ema_id AND
                    sup_removed=1";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["sup_date"] = Date_Helper::getFormattedDate($res[$i]["sup_date"]);
                $res[$i]["sup_subject"] = Mime_Helper::fixEncoding($res[$i]["sup_subject"]);
                $res[$i]["sup_from"] = Mime_Helper::fixEncoding($res[$i]["sup_from"]);
            }
            return $res;
        }
    }


    /**
     * Method used to remove all support email entries associated with
     * a specified list of support email accounts.
     *
     * @access  public
     * @param   array $ids The list of support email accounts
     * @return  boolean
     */
    function removeEmailByAccounts($ids)
    {
        if (count($ids) < 1) {
            return true;
        }
        $items = @implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_ema_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to build the server URI to connect to.
     *
     * @access  public
     * @param   array $info The email server information
     * @param   boolean $tls Whether to use TLS or not
     * @return  string The server URI to connect to
     */
    function getServerURI($info, $tls = FALSE)
    {
        $server_uri = $info['ema_hostname'] . ':' . $info['ema_port'] . '/' . strtolower($info['ema_type']);
        if (stristr($info['ema_type'], 'imap')) {
            $folder = $info['ema_folder'];
        } else {
            $folder = 'INBOX';
        }
        return '{' . $server_uri . '}' . $folder;
    }


    /**
     * Method used to connect to the provided email server.
     *
     * @access  public
     * @param   array $info The email server information
     * @return  resource The email server connection
     */
    function connectEmailServer($info)
    {
        $mbox = @imap_open(self::getServerURI($info), $info['ema_username'], $info['ema_password']);
        if ($mbox === false) {
            $error = @imap_last_error();
            if (strstr(strtolower($error), 'certificate failure')) {
                $mbox = @imap_open(self::getServerURI($info, true), $info['ema_username'], $info['ema_password']);
            } else {
                Error_Handler::logError('Error while connecting to the email server - ' . $error, __FILE__, __LINE__);
            }
        }
        return $mbox;
    }


    /**
     * Method used to get the total number of emails in the specified
     * mailbox.
     *
     * @access  public
     * @param   resource $mbox The mailbox
     * @return  integer The number of emails
     */
    function getTotalEmails($mbox)
    {
        return @imap_num_msg($mbox);
    }


    /**
     * Bounce message to sender.
     *
     * @access  public
     * @param   object  $message parsed message structure.
     * @param   array   array(ERROR_CODE, ERROR_STRING) of error to bounce
     * @return  void
     */
    function bounceMessage($message, $error)
    {
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/bounced_email.tpl.text');
        $tpl->bulkAssign(array(
            'error_code'        => $error[0],
            'error_message'     => $error[1],
            'date'              => $message->date,
            'subject'           => Mime_Helper::fixEncoding($message->subject),
            'from'              => Mime_Helper::fixEncoding($message->fromaddress),
            'to'                => Mime_Helper::fixEncoding($message->toaddress),
            'cc'                => Mime_Helper::fixEncoding(@$message->ccaddress),
        ));

        $sender_email = Mail_Helper::getEmailAddress($message->fromaddress);
        $usr_id = User::getUserIDByEmail($sender_email);
        // change the current locale
        if ($usr_id) {
            Language::set(User::getLang($usr_id));
        }

        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_Helper;
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup['from'], $sender_email,
            APP_SHORT_NAME . ': ' . ev_gettext('Postmaster notify: see transcript for details'));

        if ($usr_id) {
            Language::restore();
        }
    }

    /**
     * Method used to get the information about a specific message
     * from a given mailbox.
     *
     * XXX this function does more than that.
     *
     * @access  public
     * @param   resource $mbox The mailbox
     * @param   array $info The support email account information
     * @param   integer $num The index of the message
     * @return  void
     */
    function getEmailInfo($mbox, $info, $num)
    {
        Auth::createFakeCookie(APP_SYSTEM_USER_ID);

        // check if the current message was already seen
        if ($info['ema_get_only_new']) {
            list($overview) = @imap_fetch_overview($mbox, $num);
            if (($overview->seen) || ($overview->deleted) || ($overview->answered)) {
                return;
            }
        }

        $email = @imap_headerinfo($mbox, $num);
        $headers = imap_fetchheader($mbox, $num);
        $body = imap_body($mbox, $num);
        // check for mysterious blank messages
        if (empty($body) and empty($headers)) {
            // XXX do some error reporting?
            return;
        }
        $message_id = Mail_Helper::getMessageID($headers, $body);
        $message = $headers . $body;
        // we don't need $body anymore -- free memory
        unset($body);

        // if message_id already exists, return immediately -- nothing to do
        if (self::exists($message_id) || Note::exists($message_id)) {
            return;
        }

        $structure = Mime_Helper::decode($message, true, true);
        $message_body = $structure->body;
        if (Mime_Helper::hasAttachments($structure)) {
            $has_attachments = 1;
        } else {
            $has_attachments = 0;
        }
        // we can't trust the in-reply-to from the imap c-client, so let's
        // try to manually parse that value from the full headers
        $reference_msg_id = Mail_Helper::getReferenceMessageID($headers);

        // pass in $email by reference so it can be modified
        $workflow = Workflow::preEmailDownload($info['ema_prj_id'], $info, $mbox, $num, $message, $email, $structure);
        if ($workflow === -1) {
            return;
        }

        // route emails if neccassary
        if ($info['ema_use_routing'] == 1) {
            $setup = Setup::load();

            // we create addresses array so it can be reused
            $addresses = array();
            if (isset($email->to)) {
                foreach ($email->to as $address) {
                    $addresses[] = $address->mailbox . '@' . $address->host;
                }
            }
            if (isset($email->cc)) {
                foreach ($email->cc as $address) {
                    $addresses[] = $address->mailbox . '@' . $address->host;
                }
            }

            if (@$setup['email_routing']['status'] == 'enabled') {
                $res = Routing::getMatchingIssueIDs($addresses, 'email');
                if ($res != false) {
                    $return = Routing::route_emails($message);
                    if ($return === true) {
                        self::deleteMessage($info, $mbox, $num);
                        return;
                    }
                    // TODO: handle errors?
                    return;
                }
            }
            if (@$setup['note_routing']['status'] == 'enabled') {
                $res = Routing::getMatchingIssueIDs($addresses, 'note');
                if ($res != false) {
                    $return = Routing::route_notes($message);

                    // if leave copy of emails on IMAP server is off we can
                    // bounce on note that user had no permission to write
                    // here.
                    // otherwise proper would be to create table -
                    // eventum_bounce: bon_id, bon_message_id, bon_error

                    if ($info['ema_leave_copy']) {
                        if ($return === true) {
                            self::deleteMessage($info, $mbox, $num);
                        }
                    } else {
                        if ($return !== true) {
                            // in case of error, create bounce, but still
                            // delete email not to send bounce in next process :)
                            self::bounceMessage($email, $return);
                        }
                        self::deleteMessage($info, $mbox, $num);
                    }
                    return;
                }
            }
            if (@$setup['draft_routing']['status'] == 'enabled') {
                $res = Routing::getMatchingIssueIDs($addresses, 'draft');
                if ($res != false) {
                    $return = Routing::route_drafts($message);

                    // if leave copy of emails on IMAP server is off we can
                    // bounce on note that user had no permission to write
                    // here.
                    // otherwise proper would be to create table -
                    // eventum_bounce: bon_id, bon_message_id, bon_error

                    if ($info['ema_leave_copy']) {
                        if ($return === true) {
                            self::deleteMessage($info, $mbox, $num);
                        }
                    } else {
                        if ($return !== true) {
                            // in case of error, create bounce, but still
                            // delete email not to send bounce in next process :)
                            self::bounceMessage($email, $return);
                        }
                        self::deleteMessage($info, $mbox, $num);
                    }
                    return;
                }
            }

            // TODO:
            // disabling return here allows routing and issue auto creating from same account
            // but it will download email store it in database and do nothing
            // with it if it does not match support@ address.
            //return;
        }

        $sender_email = Mail_Helper::getEmailAddress($email->fromaddress);
        if (PEAR::isError($sender_email)) {
            $sender_email = 'Error Parsing Email <>';
        }

        $t = array(
            'ema_id'         => $info['ema_id'],
            'message_id'     => $message_id,
            'date'           => Date_Helper::convertDateGMTByTS($email->udate),
            'from'           => $sender_email,
            'to'             => @$email->toaddress,
            'cc'             => @$email->ccaddress,
            'subject'        => @$structure->headers['subject'],
            'body'           => @$message_body,
            'full_email'     => @$message,
            'has_attachment' => $has_attachments,
            // the following items are not inserted, but useful in some methods
            'headers'        => @$structure->headers
        );
        $should_create_array = self::createIssueFromEmail(
            $info, $headers, $message_body, $t['date'], $sender_email, Mime_Helper::fixEncoding( @$structure->headers['subject']), $t['to'], $t['cc']);
        $should_create_issue = $should_create_array['should_create_issue'];
        $associate_email = $should_create_array['associate_email'];
        if (!empty($should_create_array['issue_id'])) {
            $t['issue_id'] = $should_create_array['issue_id'];

            // figure out if we should change to a different email account
            $iss_prj_id = Issue::getProjectID($t['issue_id']);
            if ($info['ema_prj_id'] != $iss_prj_id) {
                $new_ema_id = Email_Account::getEmailAccount($iss_prj_id);
                if (!empty($new_ema_id)) {
                    $t['ema_id'] = $new_ema_id;
                }
            }
        }
        if (!empty($should_create_array['customer_id'])) {
            $t['customer_id'] = $should_create_array['customer_id'];
        }
        if (empty($t['issue_id'])) {
            $t['issue_id'] = 0;
        } else {
            $prj_id = Issue::getProjectID($t['issue_id']);
            Auth::createFakeCookie(APP_SYSTEM_USER_ID, $prj_id);
        }
        if ($should_create_array['type'] == 'note') {
            // assume that this is not a valid note
            $res = -1;

            if ($t['issue_id'] != 0) {
                // check if this is valid user
                $usr_id = User::getUserIDByEmail($sender_email);
                if (!empty($usr_id)) {
                    $role_id = User::getRoleByUser($usr_id, $prj_id);
                    if ($role_id > User::getRoleID("Customer")) {
                        // actually a valid user so insert the note

                        Auth::createFakeCookie($usr_id, $prj_id);

                        $users = Project::getUserEmailAssocList($prj_id, 'active', User::getRoleID('Customer'));
                        $user_emails = array_map('strtolower', array_values($users));
                        $users = array_flip($users);

                        $addresses = array();
                        $to_addresses = Mail_Helper::getEmailAddresses(@$structure->headers['to']);
                        if (count($to_addresses)) {
                            $addresses = $to_addresses;
                        }
                        $cc_addresses = Mail_Helper::getEmailAddresses(@$structure->headers['cc']);
                        if (count($cc_addresses)) {
                            $addresses = array_merge($addresses, $cc_addresses);
                        }
                        $cc_users = array();
                        foreach ($addresses as $email) {
                            if (in_array(strtolower($email), $user_emails)) {
                                $cc_users[] = $users[strtolower($email)];
                            }
                        }

                        // XXX FIXME, this is not nice thing to do
                        $_POST = array(
                            'title'                => Mail_Helper::removeExcessRe($t['subject']),
                            'note'                 => $t['body'],
                            'note_cc'              => $cc_users,
                            'add_extra_recipients' => 'yes',
                            'message_id'           => $t['message_id'],
                            'parent_id'            => $should_create_array['parent_id'],
                        );
                        $res = Note::insert($usr_id, $t['issue_id']);

                        // need to handle attachments coming from notes as well
                        if ($res != -1) {
                            Support::extractAttachments($t['issue_id'], $structure, true, $res);
                        }
                    }
                }
            }
        } else {
            // check if we need to block this email
            if (($should_create_issue == true) || (!self::blockEmailIfNeeded($t))) {
                if (!empty($t['issue_id'])) {
                    list($t['full_email'], $t['headers']) = Mail_Helper::rewriteThreadingHeaders($t['issue_id'], $t['full_email'], $t['headers'], 'email');
                }

                // make variable available for workflow to be able to detect whether this email created new issue
                $t['should_create_issue'] = $should_create_array['should_create_issue'];

                $res = self::insertEmail($t, $structure, $sup_id);
                if ($res != -1) {
                    // only extract the attachments from the email if we are associating the email to an issue
                    if (!empty($t['issue_id'])) {
                        self::extractAttachments($t['issue_id'], $structure);

                        // notifications about new emails are always external
                        $internal_only = false;
                        $assignee_only = false;
                        // special case when emails are bounced back, so we don't want a notification to customers about those
                        if (Notification::isBounceMessage($sender_email)) {
                            // broadcast this email only to the assignees for this issue
                            $internal_only = true;
                            $assignee_only = true;
                        } elseif ($should_create_issue == true) {
                            // if a new issue was created, only send a copy of the email to the assignee (if any), don't resend to the original TO/CC list
                            $assignee_only = true;
                            $internal_only = true;
                        }

                        if (Workflow::shouldAutoAddToNotificationList($info['ema_prj_id'])) {
                            self::addExtraRecipientsToNotificationList($info['ema_prj_id'], $t, $should_create_issue);
                        }

                        Notification::notifyNewEmail(Auth::getUserID(), $t['issue_id'], $t, $internal_only, $assignee_only, '', $sup_id);

                        // try to get usr_id of sender, if not, use system account
                        $addr = Mail_Helper::getEmailAddress($structure->headers['from']);
                        if (PEAR::isError($addr)) {
                            // XXX should we log or is this expected?
                            Error_Handler::logError(array($addr->getMessage()." addr: $addr", $addr->getDebugInfo()), __FILE__, __LINE__);
                            $usr_id = APP_SYSTEM_USER_ID;
                        } else {
                            $usr_id = User::getUserIDByEmail($addr);
                            if (!$usr_id) {
                                $usr_id = APP_SYSTEM_USER_ID;
                            }
                        }

                        // mark this issue as updated
                        if ((!empty($t['customer_id'])) && ($t['customer_id'] != 'NULL') && ((empty($usr_id)) || (User::getRoleByUser($usr_id, $prj_id) == User::getRoleID('Customer')))) {
                            Issue::markAsUpdated($t['issue_id'], 'customer action');
                        } else {
                            if ((!empty($usr_id)) && (User::getRoleByUser($usr_id, $prj_id) > User::getRoleID('Customer'))) {
                                Issue::markAsUpdated($t['issue_id'], 'staff response');
                            } else {
                                Issue::markAsUpdated($t['issue_id'], 'user response');
                            }
                        }
                        // log routed email
                        History::add($t['issue_id'], $usr_id, History::getTypeID('email_routed'), ev_gettext('Email routed from %1$s', $structure->headers['from']));
                    }
                }
            } else {
                $res = 1;
            }
        }

        if ($res > 0) {
            // need to delete the message from the server?
            if (!$info['ema_leave_copy']) {
                @imap_delete($mbox, $num);
            } else {
                // mark the message as already read
                @imap_setflag_full($mbox, $num, "\\Seen");
            }
        }
    }


    /**
     * Creates a new issue from an email if appropriate. Also returns if this message is related
     * to a previous message.
     *
     * @access  private
     * @param   array   $info An array of info about the email account.
     * @param   string  $headers The headers of the email.
     * @param   string  $message_body The body of the message.
     * @param   string  $date The date this message was sent
     * @param   string  $from The name and email address of the sender.
     * @param   string  $subject The subject of this message.
     * @param   array   $to An array of to addresses
     * @param   array   $cc An array of cc addresses
     * @return  array   An array of information about the message
     */
    function createIssueFromEmail($info, $headers, $message_body, $date, $from, $subject, $to, $cc)
    {
        $should_create_issue = false;
        $issue_id = '';
        $associate_email = '';
        $type = 'email';
        $parent_id = '';

        // we can't trust the in-reply-to from the imap c-client, so let's
        // try to manually parse that value from the full headers
        $references = Mail_Helper::getAllReferences($headers);

        $message_id = Mail_Helper::getMessageID($headers, $message_body);
        $workflow = Workflow::getIssueIDforNewEmail($info['ema_prj_id'], $info, $headers, $message_body, $date, $from, $subject, $to, $cc);
        if ($workflow == 'new') {
            $should_create_issue = true;
        } elseif (is_numeric($workflow)) {
            $issue_id = $workflow;
        } else {
            $setup = Setup::load();
            if (@$setup['subject_based_routing']['status'] == 'enabled') {
                // Look for issue ID in the subject line

                // look for [#XXXX] in the subject line
                if (preg_match("/\[#(\d+)\]( Note| BLOCKED)*/", $subject, $matches)) {
                    $should_create_issue = false;
                    $issue_id = $matches[1];
                    if (!Issue::exists($issue_id, false)) {
                        $issue_id = '';
                    } elseif (!empty($matches[2])) {
                        $type = 'note';
                    }
                } else {
                    $should_create_issue = true;
                }
            } else {
                // - if this email is a reply:
                if (count($references) > 0) {
                    foreach ($references as $reference_msg_id) {
                        //  -> check if the replied email exists in the database:
                        if (Note::exists($reference_msg_id)) {
                            // note exists
                            // get what issue it belongs too.
                            $issue_id = Note::getIssueByMessageID($reference_msg_id);
                            $should_create_issue = false;
                            $type = 'note';
                            $parent_id = Note::getIDByMessageID($reference_msg_id);
                            break;
                        } elseif ((self::exists($reference_msg_id)) || (Issue::getIssueByRootMessageID($reference_msg_id) != false)) {
                            // email or issue exists
                            $issue_id = self::getIssueByMessageID($reference_msg_id);
                            if (empty($issue_id)) {
                                $issue_id = Issue::getIssueByRootMessageID($reference_msg_id);
                            }
                            if (empty($issue_id)) {
                                // parent email isn't associated with issue.
                                //      --> create new issue, associate current email and replied email to this issue
                                $should_create_issue = true;
                                $associate_email = $reference_msg_id;
                            } else {
                                // parent email is associated with issue:
                                //      --> associate current email with existing issue
                                $should_create_issue = false;
                            }
                            break;
                        } else {
                            //  no matching note, email or issue:
                            //    => create new issue and associate current email with it
                            $should_create_issue = true;
                        }
                    }
                } else {
                    // - if this email is not a reply:
                    //  -> create new issue and associate current email with it
                    $should_create_issue = true;
                }
            }
        }

        $sender_email = Mail_Helper::getEmailAddress($from);
        if (PEAR::isError($sender_email)) {
            $sender_email = 'Error Parsing Email <>';
        }

        // only create a new issue if this email is coming from a known customer
        if (($should_create_issue) && ($info['ema_issue_auto_creation_options']['only_known_customers'] == 'yes') &&
                (Customer::hasCustomerIntegration($info['ema_prj_id']))) {
            list($customer_id,) = Customer::getCustomerIDByEmails($info['ema_prj_id'], array($sender_email));
            if (empty($customer_id)) {
                $should_create_issue = false;
            }
        }
        // check whether we need to create a new issue or not
        if (($info['ema_issue_auto_creation'] == 'enabled') && ($should_create_issue) && (!Notification::isBounceMessage($sender_email))) {
            $options = Email_Account::getIssueAutoCreationOptions($info['ema_id']);
            Auth::createFakeCookie(APP_SYSTEM_USER_ID, $info['ema_prj_id']);
            $issue_id = Issue::createFromEmail($info['ema_prj_id'], APP_SYSTEM_USER_ID,
                    $from, Mime_Helper::fixEncoding($subject), $message_body, @$options['category'],
                    $options['priority'], @$options['users'], $date, $message_id);

            // add sender to authorized repliers list if they are not a real user
            $sender_usr_id = User::getUserIDByEmail($sender_email, true);
            if (empty($sender_usr_id)) {
                Authorized_Replier::manualInsert($issue_id, $sender_email, false);
            }
            // associate any existing replied-to email with this new issue
            if ((!empty($associate_email)) && (!empty($reference_issue_id))) {
                $reference_sup_id = self::getIDByMessageID($associate_email);
                self::associate(APP_SYSTEM_USER_ID, $issue_id, array($reference_sup_id));
            }
        }
        // need to check crm for customer association
        if (!empty($from)) {
            $details = Email_Account::getDetails($info['ema_id']);
            if (Customer::hasCustomerIntegration($info['ema_prj_id'])) {
                // check for any customer contact association
                @list($customer_id,) = Customer::getCustomerIDByEmails($info['ema_prj_id'], array($sender_email));
            }
        }
        return array(
            'should_create_issue'   =>  $should_create_issue,
            'associate_email'   =>  $associate_email,
            'issue_id'  =>  $issue_id,
            'customer_id'   =>  @$customer_id,
            'type'      =>  $type,
            'parent_id' =>  $parent_id
        );
    }


    /**
     * Method used to close the existing connection to the email
     * server.
     *
     * @access  public
     * @param   resource $mbox The mailbox
     * @return  void
     */
    function closeEmailServer($mbox)
    {
        @imap_close($mbox);
    }


    /**
     * Builds a list of all distinct message-ids available in the provided
     * email account.
     *
     * @access  public
     * @param   integer $ema_id The support email account ID
     * @return  array The list of message-ids
     */
    function getMessageIDs($ema_id)
    {
        $stmt = "SELECT
                    DISTINCT sup_message_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_ema_id=" . Misc::escapeInteger($ema_id);
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Checks if a message already is downloaded.
     *
     * @access  public
     * @param   string $message_id The Message-ID header
     * @return  boolean
     */
    function exists($message_id)
    {
        $sql = "SELECT
                    count(*)
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                WHERE
                    sup_message_id = '" . Misc::escapeString($message_id) . "'";
        $res = DB_Helper::getInstance()->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }
        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Method used to add a new support email to the system.
     *
     * @access  public
     * @param   array $row The support email details
     * @param   object $structure The email structure object
     * @param   integer $sup_id The support ID to be passed out
     * @param   boolean $closing If this email comes from closing the issue
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function insertEmail($row, &$structure, &$sup_id, $closing = false)
    {
        // get usr_id from FROM header
        $usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($row['from']));
        if (!empty($usr_id) && !empty($row["customer_id"])) {
            $row["customer_id"] = User::getCustomerID($usr_id);
        }
        if (empty($row['customer_id'])) {
            $row['customer_id'] = "NULL";
        }

        // try to get the parent ID
        $reference_message_id = Mail_Helper::getReferenceMessageID($row['full_email']);
        $parent_id = '';
        if (!empty($reference_message_id)) {
            $parent_id = self::getIDByMessageID($reference_message_id);
            // make sure it is in the same issue
            if ((!empty($parent_id)) && ((empty($row['issue_id'])) || (@$row['issue_id'] != self::getIssueFromEmail($parent_id)))) {
                $parent_id = '';
            }
        }

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 (
                    sup_ema_id,";
        if (!empty($parent_id)) {
            $stmt .= "\nsup_parent_id,";
        }
        $stmt .= "
                    sup_iss_id,";
        if (!empty($usr_id)) {
            $stmt .= "\nsup_usr_id,\n";
        }
        $stmt .= "  sup_customer_id,
                    sup_message_id,
                    sup_date,
                    sup_from,
                    sup_to,
                    sup_cc,
                    sup_subject,
                    sup_has_attachment
                 ) VALUES (
                    " . Misc::escapeInteger($row["ema_id"]) . ",\n";
        if (!empty($parent_id)) {
            $stmt .= "$parent_id,\n";
        }
        $stmt .=    Misc::escapeInteger($row["issue_id"]) . ",";
        if (!empty($usr_id)) {
            $stmt .= "\n$usr_id,\n";
        }
        $stmt .= "
                    " . Misc::escapeInteger($row["customer_id"]) . ",
                    '" . Misc::escapeString($row["message_id"]) . "',
                    '" . Misc::escapeString($row["date"]) . "',
                    '" . Misc::escapeString($row["from"]) . "',
                    '" . Misc::escapeString(@$row["to"]) . "',
                    '" . Misc::escapeString(@$row["cc"]) . "',
                    '" . Misc::escapeString($row["subject"]) . "',
                    '" . Misc::escapeString($row["has_attachment"]) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_sup_id = DB_Helper::get_last_insert_id();
            $sup_id = $new_sup_id;
            $row['sup_id'] = $sup_id;
            // now add the body and full email to the separate table
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                     (
                        seb_sup_id,
                        seb_body,
                        seb_full_email
                     ) VALUES (
                        $new_sup_id,
                        '" . Misc::escapeString($row["body"]) . "',
                        '" . Misc::escapeString($row["full_email"]) . "'
                     )";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                Workflow::handleNewEmail(Email_Account::getProjectID($row["ema_id"]), @$row["issue_id"], $structure, $row, $closing);
                return 1;
            }
        }
    }


    /**
     * Method used to get a specific parameter in the email listing
     * cookie.
     *
     * @access  public
     * @param   string $name The name of the parameter
     * @return  mixed The value of the specified parameter
     */
    function getParam($name)
    {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        } elseif (isset($_POST[$name])) {
            return $_POST[$name];
        } elseif (($profile = Search_Profile::getProfile(Auth::getUserID(), Auth::getCurrentProject(), 'email')) && (isset($profile[$name]))) {
            return $profile[$name];
        } else {
            return "";
        }
    }


    /**
     * Method used to save the current search parameters in a cookie.
     *
     * @access  public
     * @return  array The search parameters
     */
    function saveSearchParams()
    {
        $sort_by = self::getParam('sort_by');
        $sort_order = self::getParam('sort_order');
        $rows = self::getParam('rows');
        $cookie = array(
            'rows'             => $rows ? $rows : APP_DEFAULT_PAGER_SIZE,
            'pagerRow'         => self::getParam('pagerRow'),
            'hide_associated'  => self::getParam('hide_associated'),
            "sort_by"          => $sort_by ? $sort_by : "sup_date",
            "sort_order"       => $sort_order ? $sort_order : "DESC",
            // quick filter form options
            'keywords'         => self::getParam('keywords'),
            'sender'           => self::getParam('sender'),
            'to'               => self::getParam('to'),
            'ema_id'           => self::getParam('ema_id'),
            'filter'           => self::getParam('filter')
        );
        // now do some magic to properly format the date fields
        $date_fields = array(
            'arrival_date'
        );
        foreach ($date_fields as $field_name) {
            $field = self::getParam($field_name);
            if ((empty($field)) || ($cookie['filter'][$field_name] != 'yes')) {
                continue;
            }
            $end_field_name = $field_name . '_end';
            $end_field = self::getParam($end_field_name);
            @$cookie[$field_name] = array(
                'Year'        => $field['Year'],
                'Month'       => $field['Month'],
                'Day'         => $field['Day'],
                'start'       => $field['Year'] . '-' . $field['Month'] . '-' . $field['Day'],
                'filter_type' => $field['filter_type'],
                'end'         => $end_field['Year'] . '-' . $end_field['Month'] . '-' . $end_field['Day']
            );
            @$cookie[$end_field_name] = array(
                'Year'        => $end_field['Year'],
                'Month'       => $end_field['Month'],
                'Day'         => $end_field['Day']
            );
        }
        Search_Profile::save(Auth::getUserID(), Auth::getCurrentProject(), 'email', $cookie);
        return $cookie;
    }


    /**
     * Method used to get the current sorting options used in the grid
     * layout of the emails listing page.
     *
     * @access  public
     * @param   array $options The current search parameters
     * @return  array The sorting options
     */
    function getSortingInfo($options)
    {
        $fields = array(
            "sup_from",
            "sup_customer_id",
            "sup_date",
            "sup_to",
            "sup_iss_id",
            "sup_subject"
        );
        $items = array(
            "links"  => array(),
            "images" => array()
        );
        for ($i = 0; $i < count($fields); $i++) {
            if ($options["sort_by"] == $fields[$i]) {
                $items["images"][$fields[$i]] = "images/" . strtolower($options["sort_order"]) . ".gif";
                if (strtolower($options["sort_order"]) == "asc") {
                    $sort_order = "desc";
                } else {
                    $sort_order = "asc";
                }
                $items["links"][$fields[$i]] = $_SERVER["PHP_SELF"] . "?sort_by=" . $fields[$i] . "&sort_order=" . $sort_order;
            } else {
                $items["links"][$fields[$i]] = $_SERVER["PHP_SELF"] . "?sort_by=" . $fields[$i] . "&sort_order=asc";
            }
        }
        return $items;
    }


    /**
     * Method used to get the list of emails to be displayed in the
     * grid layout.
     *
     * @access  public
     * @param   array $options The search parameters
     * @param   integer $current_row The current page number
     * @param   integer $max The maximum number of rows per page
     * @return  array The list of issues to be displayed
     */
    function getEmailListing($options, $current_row = 0, $max = 5)
    {
        $prj_id = Auth::getCurrentProject();
        $usr_id = Auth::getUserID();
        if ($max == "ALL") {
            $max = 9999999;
        }
        $start = $current_row * $max;

        $stmt = "SELECT
                    sup_id,
                    sup_ema_id,
                    sup_iss_id,
                    sup_customer_id,
                    sup_from,
                    sup_date,
                    sup_to,
                    sup_subject,
                    sup_has_attachment
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account";
        if (!empty($options['keywords'])) {
            $stmt .= "," . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body";
        }
        $stmt .= "
                    )
                    LEFT JOIN
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    ON
                        sup_iss_id = iss_id";
        $stmt .= self::buildWhereClause($options);
        $stmt .= "
                 ORDER BY
                    " . Misc::escapeString($options["sort_by"]) . " " . Misc::escapeString($options["sort_order"]);
        $total_rows = Pager::getTotalRows($stmt);
        $stmt .= "
                 LIMIT
                    " . Misc::escapeInteger($start) . ", " . Misc::escapeInteger($max);
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array(
                "list" => "",
                "info" => ""
            );
        } else {
            if ((count($res) < 1) && ($current_row > 0)) {
                // if there are no results, and the page is not the first page reset page to one and reload results
                Auth::redirect("emails.php?pagerRow=0&rows=$max");
            }
            if (Customer::hasCustomerIntegration($prj_id)) {
                $customer_ids = array();
                for ($i = 0; $i < count($res); $i++) {
                    if ((!empty($res[$i]['sup_customer_id'])) && (!in_array($res[$i]['sup_customer_id'], $customer_ids))) {
                        $customer_ids[] = $res[$i]['sup_customer_id'];
                    }
                }
                if (count($customer_ids) > 0) {
                    $company_titles = Customer::getTitles($prj_id, $customer_ids);
                }
            }
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["sup_date"] = Date_Helper::getFormattedDate($res[$i]["sup_date"]);
                $res[$i]["sup_subject"] = Mime_Helper::fixEncoding($res[$i]["sup_subject"]);
                $res[$i]["sup_from"] = join(', ', Mail_Helper::getName($res[$i]["sup_from"], true));
                if ((empty($res[$i]["sup_to"])) && (!empty($res[$i]["sup_iss_id"]))) {
                    $res[$i]["sup_to"] = "Notification List";
                } else {
                    $to = Mail_Helper::getName($res[$i]["sup_to"]);
                    // Ignore unformattable headers
                    if (!PEAR::isError($to)) {
                        $res[$i]['sup_to'] = Mime_Helper::fixEncoding($to);
                    }
                }
                if (Customer::hasCustomerIntegration($prj_id)) {
                    @$res[$i]['customer_title'] = $company_titles[$res[$i]['sup_customer_id']];
                }
            }
            $total_pages = ceil($total_rows / $max);
            $last_page = $total_pages - 1;
            return array(
                "list" => $res,
                "info" => array(
                    "current_page"  => $current_row,
                    "start_offset"  => $start,
                    "end_offset"    => $start + count($res),
                    "total_rows"    => $total_rows,
                    "total_pages"   => $total_pages,
                    "previous_page" => ($current_row == 0) ? "-1" : ($current_row - 1),
                    "next_page"     => ($current_row == $last_page) ? "-1" : ($current_row + 1),
                    "last_page"     => $last_page
                )
            );
        }
    }


    /**
     * Method used to get the list of emails to be displayed in the grid layout.
     *
     * @access  public
     * @param   array $options The search parameters
     * @return  string The where clause
     */
    function buildWhereClause($options)
    {
        $stmt = "
                 WHERE
                    sup_removed=0 AND
                    sup_ema_id=ema_id AND
                    ema_prj_id=" . Auth::getCurrentProject();
        if (!empty($options["hide_associated"])) {
            $stmt .= " AND sup_iss_id = 0";
        }
        if (!empty($options['keywords'])) {
            $stmt .= " AND sup_id=seb_sup_id ";
            $stmt .= " AND (" . Misc::prepareBooleanSearch('sup_subject', $options["keywords"]);
            $stmt .= " OR " . Misc::prepareBooleanSearch('seb_body', $options["keywords"]) . ")";
        }
        if (!empty($options['sender'])) {
            $stmt .= " AND " . Misc::prepareBooleanSearch('sup_from', $options["sender"]);
        }
        if (!empty($options['to'])) {
            $stmt .= " AND " . Misc::prepareBooleanSearch('sup_to', $options["to"]);
        }
        if (!empty($options['ema_id'])) {
            $stmt .= " AND sup_ema_id=" . $options['ema_id'];
        }
        if ((!empty($options['filter'])) && ($options['filter']['arrival_date'] == 'yes')) {
            switch ($options['arrival_date']['filter_type']) {
                case 'greater':
                    $stmt .= " AND sup_date >= '" . $options['arrival_date']['start'] . "'";
                    break;
                case 'less':
                    $stmt .= " AND sup_date <= '" . $options['arrival_date']['start'] . "'";
                    break;
                case 'between':
                    $stmt .= " AND sup_date BETWEEN '" . $options['arrival_date']['start'] . "' AND '" . $options['arrival_date']['end'] . "'";
                    break;
            }
        }

        // handle 'private' issues.
        if (Auth::getCurrentRole() < User::getRoleID("Manager")) {
            $stmt .= " AND (iss_private = 0 OR iss_private IS NULL)";
        }
        return $stmt;
    }


    /**
     * Method used to extract and associate attachments in an email
     * to the given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   mixed   $input The full body of the message or decoded email.
     * @param   boolean $internal_only Whether these files are supposed to be internal only or not
     * @param   integer $associated_note_id The note ID that these attachments should be associated with
     * @return  void
     */
    function extractAttachments($issue_id, $input, $internal_only = false, $associated_note_id = false)
    {
        if (!is_object($input)) {
            $input = Mime_Helper::decode($input, true, true);
        }

        // figure out who should be the 'owner' of this attachment
        $sender_email = strtolower(Mail_Helper::getEmailAddress($input->headers['from']));
        $usr_id = User::getUserIDByEmail($sender_email);
        $unknown_user = false;
        if (empty($usr_id)) {
            $prj_id = Issue::getProjectID($issue_id);
            if (Customer::hasCustomerIntegration($prj_id)) {
                // try checking if a customer technical contact has this email associated with it
                list(,$contact_id) = Customer::getCustomerIDByEmails($prj_id, array($sender_email));
                if (!empty($contact_id)) {
                    $usr_id = User::getUserIDByContactID($contact_id);
                }
            }
            if (empty($usr_id)) {
                // if we couldn't find a real customer by that email, set the usr_id to be the system user id,
                // and store the actual email address in the unknown_user field.
                $usr_id = APP_SYSTEM_USER_ID;
                $unknown_user = $input->headers['from'];
            }
        }
        // now for the real thing
        $attachments = Mime_Helper::getAttachments($input);
        if (count($attachments) > 0) {
            if (empty($associated_note_id)) {
                $history_log = ev_gettext("Attachment originated from an email");
            } else {
                $history_log = ev_gettext("Attachment originated from a note");
            }
            $attachment_id = Attachment::add($issue_id, $usr_id, $history_log, $internal_only, $unknown_user, $associated_note_id);
            for ($i = 0; $i < count($attachments); $i++) {
                Attachment::addFile($attachment_id, $attachments[$i]['filename'], $attachments[$i]['filetype'], $attachments[$i]['blob']);
            }
            // mark the note as having attachments (poor man's caching system)
            if ($associated_note_id != false) {
                Note::setAttachmentFlag($associated_note_id);
            }
        }
    }


    /**
     * Method used to silently associate a support email with an
     * existing issue.
     *
     * @access  public
     * @param   integer $usr_id The user ID of the person performing this change
     * @param   integer $issue_id The issue ID
     * @param   array $items The list of email IDs to associate
     * @return  integer 1 if it worked, -1 otherwise
     */
    function associateEmail($usr_id, $issue_id, $items)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_iss_id=$issue_id
                 WHERE
                    sup_id IN (" . @implode(", ", Misc::escapeInteger($items)) . ")";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            for ($i = 0; $i < count($items); $i++) {
                $full_email = self::getFullEmail($items[$i]);
                self::extractAttachments($issue_id, $full_email);
            }
            Issue::markAsUpdated($issue_id, "email");
            // save a history entry for each email being associated to this issue
            $stmt = "SELECT
                        sup_subject
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                     WHERE
                        sup_id IN (" . @implode(", ", Misc::escapeInteger($items)) . ")";
            $res = DB_Helper::getInstance()->getCol($stmt);
            for ($i = 0; $i < count($res); $i++) {
                History::add($issue_id, $usr_id, History::getTypeID('email_associated'),
                       ev_gettext('Email (subject: \'%1$s\') associated by %2$s', $res[$i], User::getFullName($usr_id)));
            }
            return 1;
        }
    }


    /**
     * Method used to associate a support email with an existing
     * issue.
     *
     * @access  public
     * @param   integer $usr_id The user ID of the person performing this change
     * @param   integer $issue_id The issue ID
     * @param   array $items The list of email IDs to associate
     * @param   boolean $authorize If the senders should be added the authorized repliers list
     * @return  integer 1 if it worked, -1 otherwise
     */
    function associate($usr_id, $issue_id, $items, $authorize = false, $add_recipients_to_nl = false)
    {
        $res = self::associateEmail($usr_id, $issue_id, $items);
        if ($res == 1) {
            $stmt = "SELECT
                        sup_id,
                        seb_full_email
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                     WHERE
                        sup_id=seb_sup_id AND
                        sup_id IN (" . @implode(", ", Misc::escapeInteger($items)) . ")";
            $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
            for ($i = 0; $i < count($res); $i++) {
                // since downloading email should make the emails 'public', send 'false' below as the 'internal_only' flag
                $structure = Mime_Helper::decode($res[$i]['seb_full_email'], true, false);
                if (Mime_Helper::hasAttachments($structure)) {
                    $has_attachments = 1;
                } else {
                    $has_attachments = 0;
                }
                $t = array(
                    'issue_id'       => $issue_id,
                    'message_id'     => @$structure->headers['message-id'],
                    'from'           => @$structure->headers['from'],
                    'to'             => @$structure->headers['to'],
                    'cc'             => @$structure->headers['cc'],
                    'subject'        => @$structure->headers['subject'],
                    'body'           => Mime_Helper::getMessageBody($structure),
                    'full_email'     => $res[$i]['seb_full_email'],
                    'has_attachment' => $has_attachments,
                    // the following items are not inserted, but useful in some methods
                    'headers'        => @$structure->headers
                );
                Notification::notifyNewEmail($usr_id, $issue_id, $t, false, false, '', $res[$i]['sup_id']);
                if ($authorize) {
                    Authorized_Replier::manualInsert($issue_id, Mail_Helper::getEmailAddress(@$structure->headers['from']), false);
                }
            }
            return 1;
        } else {
            return -1;
        }
    }


    /**
     * Method used to get the support email entry details.
     *
     * @access  public
     * @param   integer $ema_id The support email account ID
     * @param   integer $sup_id The support email ID
     * @return  array The email entry details
     */
    function getEmailDetails($ema_id, $sup_id)
    {
        // $ema_id is not needed anymore and will be re-factored away in the future
        $stmt = "SELECT
                    " . APP_TABLE_PREFIX . "support_email.*,
                    " . APP_TABLE_PREFIX . "support_email_body.*
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                 WHERE
                    sup_id=seb_sup_id AND
                    sup_id=" . Misc::escapeInteger($sup_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res['message'] = $res['seb_body'];
            $res["attachments"] = Mime_Helper::getAttachmentCIDs($res["seb_full_email"]);
            $res["timestamp"] = Date_Helper::getUnixTimestamp($res['sup_date'], 'GMT');
            $res["sup_date"] = Date_Helper::getFormattedDate($res["sup_date"]);
            $res["sup_subject"] = Mime_Helper::fixEncoding($res["sup_subject"]);
            // TRANSLATORS: %1 = email subject
            $res['reply_subject'] = Mail_Helper::removeExcessRe(ev_gettext('Re: %1$s', $res["sup_subject"]), true);
            $res["sup_from"] = Mime_Helper::fixEncoding($res["sup_from"]);
            $res["sup_to"] = Mime_Helper::fixEncoding($res["sup_to"]);

            if (!empty($res['sup_iss_id'])) {
                $res['reply_subject'] = Mail_Helper::formatSubject($res['sup_iss_id'], $res['reply_subject']);
            }

            return $res;
        }
    }


    /**
     * Returns the nth note for a specific issue. The sequence starts at 1.
     *
     * @access  public
     * @param   integer $issue_id The id of the issue.
     * @param   integer $sequence The sequential number of the email.
     * @return  array An array of data containing details about the email.
     */
    function getEmailBySequence($issue_id, $sequence)
    {
        $stmt = "SELECT
                    sup_id,
                    sup_ema_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                WHERE
                    sup_iss_id = " . Misc::escapeInteger($issue_id) . "
                ORDER BY
                    sup_id
                LIMIT " . (Misc::escapeInteger($sequence) - 1) . ", 1";
        $res = DB_Helper::getInstance()->getRow($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else if (count($res) < 1) {
            return array();
        } else {
            return self::getEmailDetails($res[1], $res[0]);
        }
    }


    /**
     * Method used to get the list of support emails associated with
     * a given set of issues.
     *
     * @access  public
     * @param   array $items List of issues
     * @return  array The list of support emails
     */
    function getListDetails($items)
    {
        $items = @implode(", ", Misc::escapeInteger($items));
        $stmt = "SELECT
                    sup_id,
                    sup_from,
                    sup_subject
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id=sup_ema_id AND
                    ema_prj_id=" . Auth::getCurrentProject() . " AND
                    sup_id IN ($items)";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["sup_subject"] = Mime_Helper::fixEncoding($res[$i]["sup_subject"]);
                $res[$i]["sup_from"] = Mime_Helper::fixEncoding($res[$i]["sup_from"]);
            }
            return $res;
        }
    }


    /**
     * Method used to get the full email message for a given support
     * email ID.
     *
     * @access  public
     * @param   integer $sup_id The support email ID
     * @return  string The full email message
     */
    function getFullEmail($sup_id)
    {
        $stmt = "SELECT
                    seb_full_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                 WHERE
                    seb_sup_id=" . Misc::escapeInteger($sup_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the email message for a given support
     * email ID.
     *
     * @access  public
     * @param   integer $sup_id The support email ID
     * @return  string The email message
     */
    function getEmail($sup_id)
    {
        $stmt = "SELECT
                    seb_body
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                 WHERE
                    seb_sup_id=" . Misc::escapeInteger($sup_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get all of the support email entries associated
     * with a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of support emails
     */
    function getEmailsByIssue($issue_id)
    {
        $usr_id = Auth::getUserID();
        $stmt = "SELECT
                    sup_id,
                    sup_ema_id,
                    sup_from,
                    sup_to,
                    sup_cc,
                    sup_date,
                    UNIX_TIMESTAMP(sup_date) as date_ts,
                    sup_subject,
                    sup_has_attachment,
                    CONCAT(sup_ema_id, '-', sup_id) AS composite_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_iss_id=" . Misc::escapeInteger($issue_id) . "
                 ORDER BY
                    sup_id ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (count($res) == 0) {
                return "";
            } else {
                for ($i = 0; $i < count($res); $i++) {
                    $res[$i]["sup_date"] = Date_Helper::getFormattedDate($res[$i]["sup_date"]);
                    $res[$i]["sup_subject"] = Mime_Helper::fixEncoding($res[$i]["sup_subject"]);
                    $res[$i]["sup_from"] = Mime_Helper::fixEncoding($res[$i]["sup_from"]);
                    $res[$i]["sup_to"] = Mime_Helper::fixEncoding($res[$i]["sup_to"]);
                    $res[$i]["sup_cc"] = Mime_Helper::fixEncoding($res[$i]["sup_cc"]);
                }
                return $res;
            }
        }
    }


    /**
     * Method used to update all of the selected support emails as
     * 'removed' ones.
     *
     * @access  public
     * @return  integer 1 if it worked, -1 otherwise
     */
    function removeEmails()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["item"]));
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_removed=1
                 WHERE
                    sup_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to remove the association of all support emails
     * for a given issue.
     *
     * @access  public
     * @return  integer 1 if it worked, -1 otherwise
     */
    function removeAssociation()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["item"]));
        $stmt = "SELECT
                    sup_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id IN ($items)";
        $issue_id = DB_Helper::getInstance()->getOne($stmt);

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_iss_id=0
                 WHERE
                    sup_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($issue_id);
            // save a history entry for each email being associated to this issue
            $stmt = "SELECT
                        sup_id,
                        sup_subject
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                     WHERE
                        sup_id IN ($items)";
            $subjects = DB_Helper::getInstance()->getAssoc($stmt);
            for ($i = 0; $i < count($_POST["item"]); $i++) {
                History::add($issue_id, Auth::getUserID(), History::getTypeID('email_disassociated'),
                                ev_gettext('Email (subject: \'%1$s\') disassociated by %2$s', $subjects[$_POST["item"][$i]], User::getFullName(Auth::getUserID())));
            }
            return 1;
        }
    }


    /**
     * Checks whether the given email address is allowed to send emails in the
     * issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $sender_email The email address
     * @return  boolean
     */
    public static function isAllowedToEmail($issue_id, $sender_email)
    {
        $prj_id = Issue::getProjectID($issue_id);

        // check the workflow
        $workflow_can_email = Workflow::canEmailIssue($prj_id, $issue_id, $sender_email);
        if ($workflow_can_email != null) {
            return $workflow_can_email;
        }

        $is_allowed = true;
        $sender_usr_id = User::getUserIDByEmail($sender_email, true);
        if (empty($sender_usr_id)) {
            if (Customer::hasCustomerIntegration($prj_id)) {
                // check for a customer contact with several email addresses
                $customer_id = Issue::getCustomerID($issue_id);
                $contact_emails = array_keys(Customer::getContactEmailAssocList($prj_id, $customer_id));
                $contact_emails = array_map('strtolower', $contact_emails);
                if ((!in_array(strtolower($sender_email), $contact_emails)) &&
                        (!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email))) {
                    $is_allowed = false;
                }
            } else {
                if (!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email)) {
                    $is_allowed = false;
                }
            }
        } else {
            // check if this user is not a customer and
            // also not in the assignment list for the current issue and
            // also not in the authorized repliers list
            // also not the reporter
            $details = Issue::getDetails($issue_id);
            if ($sender_usr_id == $details['iss_usr_id']) {
                $is_allowed = true;
            } elseif ((User::isPartner($sender_usr_id)) && (in_array(User::getPartnerID($sender_usr_id), Partner::getPartnerCodesByIssue($issue_id)))) {
                $is_allowed = true;
            } elseif ((!Issue::canAccess($issue_id, $sender_usr_id)) && (!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email))) {
                $is_allowed = false;
            } elseif ((!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email)) &&
                    (!Issue::isAssignedToUser($issue_id, $sender_usr_id)) &&
                    (User::getRoleByUser($sender_usr_id, Issue::getProjectID($issue_id)) != User::getRoleID('Customer'))) {
                $is_allowed = false;
            } elseif ((User::getRoleByUser($sender_usr_id, Issue::getProjectID($issue_id)) == User::getRoleID('Customer')) &&
                    (User::getCustomerID($sender_usr_id) != Issue::getCustomerID($issue_id))) {
                $is_allowed = false;
            }
        }
        return $is_allowed;
    }


    /**
     * Method used to build the headers of a web-based message.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $message_id The message-id
     * @param   string $from The sender of this message
     * @param   string $to The primary recipient of this message
     * @param   string $cc The extra recipients of this message
     * @param   string $body The message body
     * @param   string $in_reply_to The message-id that we are replying to
     * @param   array $attachments Array with attachment information
     * @return  string The full email
     */
    function buildFullHeaders($issue_id, $message_id, $from, $to, $cc, $subject, $body, $in_reply_to, $attachments = null)
    {
        // hack needed to get the full headers of this web-based email
        $mail = new Mail_Helper;
        $mail->setTextBody($body);

        $body = $mail->mime->get(array('text_charset' => APP_CHARSET, 'head_charset' => APP_CHARSET, 'text_encoding' => APP_EMAIL_ENCODING));

        if (!empty($issue_id)) {
            $mail->setHeaders(array("Message-Id" => $message_id));
        } else {
            $issue_id = 0;
        }

        // if there is no existing in-reply-to header, get the root message for the issue
        if (($in_reply_to == false) && (!empty($issue_id))) {
            $in_reply_to = Issue::getRootMessageID($issue_id);
        }

        if ($in_reply_to) {
            $mail->setHeaders(array("In-Reply-To" => $in_reply_to));
        }
        if ($attachments) {
            for ($i = 0; $i < count($attachments['name']); $i++) {
                if (!empty($attachments["name"][$i])) {
                    $mail->addAttachment($attachments["name"][$i],
                                         file_get_contents($attachments["tmp_name"][$i]),
                                         $attachments["type"][$i]);
                }
            }
        }
        $cc = trim($cc);
        if (!empty($cc)) {
            $cc = str_replace(",", ";", $cc);
            $ccs = explode(";", $cc);
            for ($i = 0; $i < count($ccs); $i++) {
                if (!empty($ccs[$i])) {
                    $mail->addCc($ccs[$i]);
                }
            }
        }
        return $mail->getFullHeaders($from, $to, $subject);
    }


    /**
     * Method used to send emails directly from the sender to the
     * recipient. This will not re-write the sender's email address
     * to issue-xxxx@ or whatever.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $from The sender of this message
     * @param   string $to The primary recipient of this message
     * @param   string $cc The extra recipients of this message
     * @param   string $subject The subject of this message
     * @param   string $body The message body
     * @param   string $message_id The message-id
     * @param   integer $sender_usr_id The ID of the user sending this message.
     * @param   array $attachment An array with attachment information.
     * @return  void
     */
    function sendDirectEmail($issue_id, $from, $to, $cc, $subject, $body, $attachment, $message_id, $sender_usr_id = false)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $subject = Mail_Helper::formatSubject($issue_id, $subject);
        $recipients = self::getRecipientsCC($cc);
        $recipients[] = $to;
        // send the emails now, one at a time
        foreach ($recipients as $recipient) {
            $mail = new Mail_Helper;
            if (!empty($issue_id)) {
                // add the warning message to the current message' body, if needed
                $fixed_body = Mail_Helper::addWarningMessage($issue_id, $recipient, $body, array());
                $mail->setHeaders(array(
                    "Message-Id" => $message_id
                ));
                // skip users who don't have access to this issue (but allow non-users and users without access to this project) to get emails
                $recipient_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($recipient), true);
                if ((((!empty($recipient_usr_id)) && ((!Issue::canAccess($issue_id, $recipient_usr_id)) && (User::getRoleByUser($recipient_usr_id, $prj_id) != NULL)))) ||
                        (empty($recipient_usr_id)) && (Issue::isPrivate($issue_id))) {
                    continue;
                }
            } else {
                $fixed_body = $body;
            }
            if (User::getRoleByUser(User::getUserIDByEmail(Mail_Helper::getEmailAddress($from)), Issue::getProjectID($issue_id)) == User::getRoleID("Customer")) {
                $type = 'customer_email';
            } else {
                $type = 'other_email';
            }
            if ($attachment && !empty($attachment["name"][0])) {
                $mail->addAttachment($attachment["name"][0],
                                     file_get_contents($attachment["tmp_name"][0]),
                                     $attachment["type"][0]);
            }
            $mail->setTextBody($fixed_body);
            $mail->send($from, $recipient, $subject, TRUE, $issue_id, $type, $sender_usr_id);
        }
    }


    /**
     * Method used to parse the Cc list in a string format and return
     * an array of the email addresses contained within.
     *
     * @access  public
     * @param   string $cc The Cc list
     * @return  array The list of email addresses
     */
    function getRecipientsCC($cc)
    {
        $cc = trim($cc);
        if (empty($cc)) {
            return array();
        } else {
            $cc = str_replace(",", ";", $cc);
            return explode(";", $cc);
        }
    }


    /**
     * Method used to send an email from the user interface.
     *
     * @access  public
     * @return  integer 1 if it worked, -1 otherwise
     */
    function sendEmail($parent_sup_id = FALSE)
    {
        // if we are replying to an existing email, set the In-Reply-To: header accordingly
        if ($parent_sup_id) {
            $in_reply_to = self::getMessageIDByID($parent_sup_id);
        } else {
            $in_reply_to = false;
        }

        // get ID of whoever is sending this.
        $sender_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($_POST["from"]));
        if (empty($sender_usr_id)) {
            $sender_usr_id = false;
        }

        // get type of email this is
        if (!empty($_POST['type'])) {
            $type = $_POST['type'];
        } else {
            $type = '';
        }


        // remove extra 'Re: ' from subject
        $_POST['subject'] = Mail_Helper::removeExcessRe($_POST['subject'], true);
        $internal_only = false;
        $message_id = Mail_Helper::generateMessageID();
        // hack needed to get the full headers of this web-based email
        $full_email = self::buildFullHeaders($_POST["issue_id"], $message_id, $_POST["from"],
                $_POST["to"], $_POST["cc"], $_POST["subject"], $_POST["message"], $in_reply_to, @$_FILES["attachment"]);

        // email blocking should only be done if this is an email about an associated issue
        if (!empty($_POST['issue_id'])) {
            $user_info = User::getNameEmail(Auth::getUserID());
            // check whether the current user is allowed to send this email to customers or not
            if (!self::isAllowedToEmail($_POST["issue_id"], $user_info['usr_email'])) {
                // add the message body as a note
                $_POST['full_message'] = $full_email;
                $_POST['title'] = $_POST["subject"];
                $_POST['note'] = Mail_Helper::getCannedBlockedMsgExplanation() . $_POST["message"];
                Note::insert(Auth::getUserID(), $_POST["issue_id"], false, true, false, true, true);
                Workflow::handleBlockedEmail(Issue::getProjectID($_POST['issue_id']), $_POST['issue_id'], $_POST, 'web');
                return 1;
            }
        }

        // only send a direct email if the user doesn't want to add the Cc'ed people to the notification list
        if ((@$_POST['add_unknown'] == 'yes') || (Workflow::shouldAutoAddToNotificationList(Issue::getProjectID($_POST['issue_id'])))) {
            if (!empty($_POST['issue_id'])) {
                // add the recipients to the notification list of the associated issue
                $recipients = array($_POST['to']);
                $recipients = array_merge($recipients, self::getRecipientsCC($_POST['cc']));
                for ($i = 0; $i < count($recipients); $i++) {
                    if ((!empty($recipients[$i])) && (!Notification::isIssueRoutingSender($_POST["issue_id"], $recipients[$i]))) {
                        Notification::subscribeEmail(Auth::getUserID(), $_POST["issue_id"], Mail_Helper::getEmailAddress($recipients[$i]),
                                        Notification::getDefaultActions($_POST['issue_id'], $recipients[$i], 'add_unknown_user'));
                    }
                }
            }
        } else {
            // Usually when sending out emails associated to an issue, we would
            // simply insert the email in the table and call the Notification::notifyNewEmail() method,
            // but on this case we need to actually send the email to the recipients that are not
            // already in the notification list for the associated issue, if any.
            // In the case of replying to an email that is not yet associated with an issue, then
            // we are always directly sending the email, without using any notification list
            // functionality.
            if (!empty($_POST['issue_id'])) {
                // send direct emails only to the unknown addresses, and leave the rest to be
                // catched by the notification list
                $from = Notification::getFixedFromHeader($_POST['issue_id'], $_POST['from'], 'issue');
                // build the list of unknown recipients
                if (!empty($_POST['to'])) {
                    $recipients = array($_POST['to']);
                    $recipients = array_merge($recipients, self::getRecipientsCC($_POST['cc']));
                } else {
                    $recipients = self::getRecipientsCC($_POST['cc']);
                }
                $unknowns = array();
                for ($i = 0; $i < count($recipients); $i++) {
                    if (!Notification::isSubscribedToEmails($_POST['issue_id'], $recipients[$i])) {
                        $unknowns[] = $recipients[$i];
                    }
                }
                if (count($unknowns) > 0) {
                    $to = array_shift($unknowns);
                    $cc = implode('; ', $unknowns);
                    // send direct emails
                    self::sendDirectEmail($_POST['issue_id'], $from, $to, $cc,
                        $_POST['subject'], $_POST['message'], $_FILES['attachment'], $message_id, $sender_usr_id);
                }
            } else {
                // send direct emails to all recipients, since we don't have an associated issue
                $project_info = Project::getOutgoingSenderAddress(Auth::getCurrentProject());
                // use the project-related outgoing email address, if there is one
                if (!empty($project_info['email'])) {
                    $from = Mail_Helper::getFormattedName(User::getFullName(Auth::getUserID()), $project_info['email']);
                } else {
                    // otherwise, use the real email address for the current user
                    $from = User::getFromHeader(Auth::getUserID());
                }
                // send direct emails
                self::sendDirectEmail($_POST['issue_id'], $from, $_POST['to'], $_POST['cc'],
                        $_POST['subject'], $_POST['message'], $_FILES['attachment'], $message_id);
            }
        }

        $t = array(
            'customer_id'    => 'NULL',
            'issue_id'       => $_POST["issue_id"] ? $_POST["issue_id"] : 0,
            'ema_id'         => $_POST['ema_id'],
            'message_id'     => $message_id,
            'date'           => Date_Helper::getCurrentDateGMT(),
            'from'           => $_POST['from'],
            'to'             => $_POST['to'],
            'cc'             => @$_POST['cc'],
            'subject'        => @$_POST['subject'],
            'body'           => $_POST['message'],
            'full_email'     => $full_email,
            'has_attachment' => @$_FILES['attachment'] && !empty($_FILES['attachment']['name'][0]) ? 1 : 0
        );
        // associate this new email with a customer, if appropriate
        if (Auth::getCurrentRole() == User::getRoleID('Customer')) {
            $customer_id = User::getCustomerID(Auth::getUserID());
            if ((!empty($customer_id)) && ($customer_id != -1)) {
                $t['customer_id'] = $customer_id;
            }
        }
        if ($t['has_attachment'] == 1) {
            $_POST["file_description"] = "Attachment originated from outgoing email";
            $attachment_id = Attachment::attach($sender_usr_id);
        }
        $structure = Mime_Helper::decode($full_email, true, false);
        $t['headers'] = $structure->headers;
        $res = self::insertEmail($t, $structure, $sup_id);
        if (!empty($_POST["issue_id"])) {
            // need to send a notification
            Notification::notifyNewEmail(Auth::getUserID(), $_POST["issue_id"], $t, $internal_only, false, $type, $sup_id);
            // mark this issue as updated
            if ((!empty($t['customer_id'])) && ($t['customer_id'] != 'NULL') && ((empty($usr_id)) || (User::getRoleByUser($usr_id, $prj_id) == User::getRoleID('Customer')))) {
                Issue::markAsUpdated($_POST["issue_id"], 'customer action');
            } else {
                if ((!empty($sender_usr_id)) && (User::getRoleByUser($sender_usr_id, Issue::getProjectID($_POST['issue_id'])) > User::getRoleID('Customer'))) {
                    Issue::markAsUpdated($_POST["issue_id"], 'staff response');
                } else {
                    Issue::markAsUpdated($_POST["issue_id"], 'user response');
                }
            }
            // save a history entry for this
            History::add($_POST["issue_id"], Auth::getUserID(), History::getTypeID('email_sent'),
                            ev_gettext('Outgoing email sent by %1$s', User::getFullName(Auth::getUserID())));
        }

        return 1;
    }


    /**
     * Method used to get the message-id associated with a given support
     * email entry.
     *
     * @access  public
     * @param   integer $sup_id The support email ID
     * @return  integer The email ID
     */
    function getMessageIDByID($sup_id)
    {
        $stmt = "SELECT
                    sup_message_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id=" . Misc::escapeInteger($sup_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the support ID associated with a given support
     * email message-id.
     *
     * @access  public
     * @param   string $message_id The message ID
     * @return  integer The email ID
     */
    function getIDByMessageID($message_id)
    {
        $stmt = "SELECT
                    sup_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_message_id='" . Misc::escapeString($message_id) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if (empty($res)) {
                return false;
            } else {
                return $res;
            }
        }
    }


    /**
     * Method used to get the issue ID associated with a given support
     * email message-id.
     *
     * @access  public
     * @param   string $message_id The message ID
     * @return  integer The issue ID
     */
    function getIssueByMessageID($message_id)
    {
        $stmt = "SELECT
                    sup_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_message_id='" . Misc::escapeString($message_id) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the issue ID associated with a given support
     * email entry.
     *
     * @access  public
     * @param   integer $sup_id The support email ID
     * @return  integer The issue ID
     */
    function getIssueFromEmail($sup_id)
    {
        $stmt = "SELECT
                    sup_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id=" . Misc::escapeInteger($sup_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Returns the message-id of the parent email.
     *
     * @access  public
     * @param   string $msg_id The message ID
     * @return  string The message id of the parent email or false
     */
    function getParentMessageIDbyMessageID($msg_id)
    {
        $sql = "SELECT
                    parent.sup_message_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email child,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email parent
                WHERE
                    parent.sup_id = child.sup_parent_id AND
                    child.sup_message_id = '" . Misc::escapeString($msg_id) . "'";
        $res = DB_Helper::getInstance()->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if (empty($res)) {
                return false;
            }
            return $res;
        }

    }


    /**
     * Returns the number of emails sent by a user in a time range.
     *
     * @access  public
     * @param   string $usr_id The ID of the user
     * @param   integer $start The timestamp of the start date
     * @param   integer $end The timestanp of the end date
     * @param   boolean $associated If this should return emails associated with issues or non associated emails.
     * @return  integer The number of emails sent by the user.
     */
    function getSentEmailCountByUser($usr_id, $start, $end, $associated)
    {
        $usr_info = User::getNameEmail($usr_id);
        $stmt = "SELECT
                    COUNT(sup_id)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id = sup_ema_id AND
                    ema_prj_id = " . Auth::getCurrentProject() . " AND
                    sup_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "' AND
                    sup_from LIKE '%" . Misc::escapeString($usr_info["usr_email"]) . "%' AND
                    sup_iss_id ";
        if ($associated == true) {
            $stmt .= "!= 0";
        } else {
            $stmt .= "= 0";
        }
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return $res;
    }


    /**
     * Returns the projectID based on the email account
     *
     * @access  public
     * @param   integer $ema_id The id of the email account.
     * @return  integer The ID of the of the project.
     */
    function getProjectByEmailAccount($ema_id)
    {
        static $returns;

        if (!empty($returns[$ema_id])) {
            return $returns[$ema_id];
        }

        $stmt = "SELECT
                    ema_prj_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id = " . Misc::escapeInteger($ema_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        $returns[$ema_id] = $res;
        return $res;
    }


    /**
     * Moves an email from one account to another.
     *
     * @access  public
     * @param   integer $sup_id The ID of the message.
     * @param   integer $current_ema_id The ID of the account the message is currently in.
     * @param   integer $new_ema_id The ID of the account to move the message too.
     * @return  integer -1 if there was error moving the message, 1 otherwise.
     */
    function moveEmail($sup_id, $current_ema_id, $new_ema_id)
    {
        $usr_id = Auth::getUserID();
        $email = self::getEmailDetails($current_ema_id, $sup_id);
        if (!empty($email['sup_iss_id'])) {
            return -1;
        }

        $info = Email_Account::getDetails($new_ema_id);
        $full_email = self::getFullEmail($sup_id);
        $structure = Mime_Helper::decode($full_email, true, true);
        $headers = '';
        foreach ($structure->headers as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $headers .= "$key: $value\n";
        }

        // handle auto creating issues (if needed)
        $should_create_array = self::createIssueFromEmail($info, $headers, $email['seb_body'], $email['timestamp'], $email['sup_from'], $email['sup_subject']);
        $should_create_issue = $should_create_array['should_create_issue'];
        $associate_email = $should_create_array['associate_email'];
        $issue_id = $should_create_array['issue_id'];
        $customer_id = $should_create_array['customer_id'];

        if (empty($issue_id)) {
            $issue_id = 0;
        }
        if (empty($customer_id)) {
            $customer_id = 'NULL';
        }

        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                SET
                    sup_ema_id = " . Misc::escapeInteger($new_ema_id) . ",
                    sup_iss_id = " . Misc::escapeInteger($issue_id) . ",
                    sup_customer_id = " . Misc::escapeInteger($customer_id) . "
                WHERE
                    sup_id = " . Misc::escapeInteger($sup_id) . " AND
                    sup_ema_id = " . Misc::escapeInteger($current_ema_id);
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }

        $row = array(
            'customer_id'    => $customer_id,
            'issue_id'       => $issue_id,
            'ema_id'         => $new_ema_id,
            'message_id'     => $email['sup_message_id'],
            'date'           => $email['timestamp'],
            'from'           => $email['sup_from'],
            'to'             => $email['sup_to'],
            'cc'             => $email['sup_cc'],
            'subject'        => $email['sup_subject'],
            'body'           => $email['seb_body'],
            'full_email'     => $email['seb_full_email'],
            'has_attachment' => $email['sup_has_attachment']
        );
        Workflow::handleNewEmail(self::getProjectByEmailAccount($new_ema_id), $issue_id, $structure, $row);
        return 1;
    }


    /**
     * Deletes the specified message from the server
     * NOTE: YOU STILL MUST call imap_expunge($mbox) to permanently delete the message.
     *
     * @param   array $info An array of email account information
     * @param   object $mbox The mailbox object
     * @param   integer $num The number of the message to delete.
     */
    function deleteMessage($info, $mbox, $num)
    {
        // need to delete the message from the server?
        if (!$info['ema_leave_copy']) {
            @imap_delete($mbox, $num);
        } else {
            // mark the message as already read
            @imap_setflag_full($mbox, $num, "\\Seen");
        }
    }


    /**
     * Check if this email needs to be blocked and if so, block it.
     *
     *
     */
    function blockEmailIfNeeded($email)
    {
        if (empty($email['issue_id'])) {
            return false;
        }

        $issue_id = $email['issue_id'];
        $prj_id = Issue::getProjectID($issue_id);
        $sender_email = strtolower(Mail_Helper::getEmailAddress($email['from']));
        list($text_headers, $body) = Mime_Helper::splitHeaderBody($email['full_email']);
        if ((Mail_Helper::isVacationAutoResponder($email['headers'])) || (Notification::isBounceMessage($sender_email)) ||
                (!self::isAllowedToEmail($issue_id, $sender_email))) {
            // add the message body as a note
            $_POST = array(
                'full_message'=> $email['full_email'],
                'title'       => @$email['headers']['subject'],
                'note'        => Mail_Helper::getCannedBlockedMsgExplanation($issue_id) . $email['body'],
                'message_id'  => Mail_Helper::getMessageID($text_headers, $body),
            );
            // avoid having this type of message re-open the issue
            if (Mail_Helper::isVacationAutoResponder($email['headers'])) {
                $closing = true;
                $notify = false;
            } else {
                $closing = false;
                $notify = true;
            }
            $res = Note::insert(Auth::getUserID(), $issue_id, $email['headers']['from'], false, $closing, $notify, true);
            // associate the email attachments as internal-only files on this issue
            if ($res != -1) {
                self::extractAttachments($issue_id, $email['full_email'], true, $res);
            }

            $_POST['issue_id'] = $issue_id;
            $_POST['from'] = $sender_email;

            // avoid having this type of message re-open the issue
            if (Mail_Helper::isVacationAutoResponder($email['headers'])) {
                $email_type = 'vacation-autoresponder';
            } else {
                $email_type = 'routed';
            }
            Workflow::handleBlockedEmail($prj_id, $issue_id, $_POST, $email_type);

            // try to get usr_id of sender, if not, use system account
            $usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($email['from']), true);
            if (!$usr_id) {
                $usr_id = APP_SYSTEM_USER_ID;
            }
            // log blocked email
            History::add($issue_id, $usr_id, History::getTypeID('email_blocked'), ev_gettext('Email from \'%1$s\' blocked', $email['from']));
            return true;
        }
        return false;
    }


    function addExtraRecipientsToNotificationList($prj_id, $email, $is_auto_created = false)
    {
        if ((empty($email['to'])) && (!empty($email['sup_to']))) {
            $email['to'] = $email['sup_to'];
        }
        if ((empty($email['cc'])) && (!empty($email['sup_cc']))) {
            $email['cc'] = $email['sup_cc'];
        }


        $project_details = Project::getDetails($prj_id);
        $addresses_not_too_add = explode(',', strtolower($project_details['prj_mail_aliases']));
        array_push($addresses_not_too_add, $project_details['prj_outgoing_sender_email']);

        $addresses = array();
        $to_addresses = Mail_Helper::getEmailAddresses(@$email['to']);
        if (count($to_addresses)) {
            $addresses = $to_addresses;
        }
        $cc_addresses = Mail_Helper::getEmailAddresses(@$email['cc']);
        if (count($cc_addresses)) {
            $addresses = array_merge($addresses, $cc_addresses);
        }
        $subscribers = Notification::getSubscribedEmails($email['issue_id']);
        foreach ($addresses as $address) {
            $address = strtolower($address);
            if ((!in_array($address, $subscribers)) && (!in_array($address, $addresses_not_too_add))) {
                Notification::subscribeEmail(Auth::getUserID(), $email['issue_id'], $address, Notification::getDefaultActions($email['issue_id'], $address, 'add_extra_recipients'));
                if ($is_auto_created) {
                    Notification::notifyAutoCreatedIssue($prj_id, $email['issue_id'], $email['from'], $email['date'], $email['subject'], $address);
                }
            }
        }
    }


    /**
     * Returns the sequential number of the specified email ID.
     *
     * @param   integer $sup_id The email ID
     * @return  integer The sequence number of the email
     */
    public static function getSequenceByID($sup_id)
    {
        if (empty($sup_id)) {
            return '';
        }
        $res = DB_Helper::getInstance()->query("SET @sup_seq = 0");
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }
        $issue_id = Support::getIssueFromEmail($sup_id);
        $sql = "SELECT
                	sup_id,
                	@sup_seq := @sup_seq+1
                FROM
                	" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                WHERE
                	sup_iss_id = " . $issue_id . "
                ORDER BY
                    sup_id ASC";
        $res = DB_Helper::getInstance()->getAssoc($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }
        return @$res[$sup_id];
    }
}
