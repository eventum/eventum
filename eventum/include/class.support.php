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
// @(#) $Id: s.class.support.php 1.79 04/01/23 21:47:17-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the email feature of 
 * the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.pager.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.email_account.php");

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
                    sup_id IN (" . implode(', ', $sup_ids) . ")";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            for ($i = 0; $i < count($res); $i++) {
                // try to re-use an open connection to the imap server
                if (!in_array($res[$i]['sup_ema_id'], array_keys($accounts))) {
                    $accounts[$res[$i]['sup_ema_id']] = Support::connectEmailServer(Email_Account::getDetails($res[$i]['sup_ema_id']));
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
                // remove the email record from the table
                Support::removeEmail($res[$i]['sup_id']);
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
                    sup_id=$sup_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
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
        $options = Support::saveSearchParams();

        $stmt = "SELECT
                    sup_id,
                    sup_ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account";
        if (!empty($options['keywords'])) {
            $stmt .= "," . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body";
        }
        $stmt .= Support::buildWhereClause($options);
        $stmt .= "
                 ORDER BY
                    " . $options["sort_by"] . " " . $options["sort_order"];
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
                    sup_iss_id=$issue_id
                 ORDER BY
                    sup_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
        $path = APP_PATH . "misc/routed_emails/";
        list($usec,) = explode(" ", microtime());
        $filename = date('dmY.His.') . $usec . '.email.txt';
        $fp = @fopen($path . $filename, 'w');
        @fwrite($fp, $message);
        @fclose($fp);
        @chmod($path . $filename, 0777);
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
                    sup_id IN (" . implode(", ", $sup_ids) . ")";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
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
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["item"]);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_removed=0
                 WHERE
                    sup_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["sup_date"] = Date_API::getFormattedDate($res[$i]["sup_date"]);
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
        $items = @implode(", ", $ids);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_ema_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $mbox = @imap_open(Support::getServerURI($info), $info['ema_username'], $info['ema_password']);
        if ($mbox === FALSE) {
            $errors = @imap_errors();
            if (strstr(strtolower($errors[0]), 'certificate failure')) {
                $mbox = @imap_open(Support::getServerURI($info, TRUE), $info['ema_username'], $info['ema_password']);
            } else {
                Error_Handler::logError('Error while connecting to the email server - ' . $errors[0], __FILE__, __LINE__);
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
     * Returns the referenced message-id for a given reply.
     *
     * @access  public
     * @param   string $text_headers The full headers of the reply
     * @return  string The message-id of the original email
     */
    function getReferenceMessageID($text_headers)
    {
        $references = array();
        if (preg_match('/^In-Reply-To: (.*)/m', $text_headers, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/^References: (.+?)(\r?\n\r?\n|\r?\n\r?\S)/sm', $text_headers, $matches)) {
            $references = explode("\n", trim($matches[1]));
            $references = array_map('trim', $references);
            // return the first message-id in the list of references
            return $references[0];
        }
        return '';
    }


    /**
     * Method used to get the information about a specific message
     * from a given mailbox.
     *
     * @access  public
     * @param   resource $mbox The mailbox
     * @param   array $info The support email account information
     * @param   integer $num The index of the message
     * @return  array The message information
     */
    function getEmailInfo($mbox, $info, $num)
    {
        // check if the current message was already seen
        if ($info['ema_get_only_new']) {
            list($overview) = @imap_fetch_overview($mbox, $num);
            if (($overview->seen) || ($overview->deleted) || ($overview->answered)) {
                return false;
            }
        }
        $email = @imap_headerinfo($mbox, $num);
        if (!Support::exists($info['ema_id'], @$email->message_id)) {
            $body = imap_body($mbox, $num);
            $headers = imap_fetchheader($mbox, $num);
            $message = $headers . $body;
            // check for mysterious blank messages
            if (empty($message)) {
                return '';
            }
            $parts = array();
            $structure = Mime_Helper::decode($message, true, true);
            $message_body = Mime_Helper::getMessageBody(&$structure);
            Mime_Helper::parse_output($structure, $parts);
            if (@count($parts["attachments"]) > 0) {
                $has_attachments = 1;
            } else {
                $has_attachments = 0;
            }
            // we can't trust the in-reply-to from the imap c-client, so let's
            // try to manually parse that value from the full headers
            $reference_msg_id = Support::getReferenceMessageID($headers);

            $t = array(
                'ema_id'         => $info['ema_id'],
                'message_id'     => @$email->message_id,
                'date'           => @Date_API::getDateGMTByTS($email->udate),
                'from'           => @$email->fromaddress,
                'to'             => @$email->toaddress,
                'cc'             => @$email->ccaddress,
                'subject'        => @$email->subject,
                'body'           => @$message_body,
                'full_email'     => @$message,
                'has_attachment' => $has_attachments
            );
            $should_create_issue = false;
            $associate_email = '';
            // - if this email is a reply:
            if (!empty($reference_msg_id)) {
                //  -> check if the replied email exists in the database:
                if (Support::exists($info['ema_id'], $reference_msg_id)) {
                    //   -- if it does:
                    //    => check if the replied email is already associated with an issue
                    $reference_issue_id = Support::getIssueByMessageID($reference_msg_id);
                    if (empty($reference_issue_id)) {
                        //     == if it isn't:
                        //      --> create new issue, associate current email and replied email to this issue
                        $should_create_issue = true;
                        $associate_email = $reference_msg_id;
                    } else {
                        //     == if it is:
                        //      --> associate current email with existing issue
                        $should_create_issue = false;
                        $t['issue_id'] = $reference_issue_id;
                    }
                } else {
                    //   -- if it doesn't:
                    //    => create new issue and associate current email with it
                    $should_create_issue = true;
                }
            } else {
                // - if this email is not a reply:
                //  -> create new issue and associate current email with it
                $should_create_issue = true;
            }
            $sender_email = Mail_API::getEmailAddress($email->fromaddress);
            // only create a new issue if this email is coming from a known customer
            if (($should_create_issue) && ($info['ema_issue_auto_creation_options']['only_known_customers'] == 'yes') &&
                    (Customer::hasCustomerIntegration($info['ema_prj_id']))) {
                list($customer_id,) = Customer::getCustomerIDByEmails($info['ema_prj_id'], array($sender_email));
                if (empty($customer_id)) {
                    $should_create_issue = false;
                }
            }
            // check whether we need to create a new issue or not
            if (($info['ema_issue_auto_creation'] == 'enabled') && ($should_create_issue)) {
                $options = Email_Account::getIssueAutoCreationOptions($info['ema_id']);
                $new_issue_id = @Issue::createFromEmail($info['ema_prj_id'], APP_SYSTEM_USER_ID, 
                        $email->fromaddress, $email->subject, $message_body, $options['category'], 
                        $options['priority'], @$options['users'], $t['date']);
                $t['issue_id'] = $new_issue_id;
                // associate any existing replied-to email with this new issue
                if ((!empty($associate_email)) && (!empty($t['issue_id']))) {
                    $reference_sup_id = Support::getIDByMessageID($associate_email);
                    Support::associate(APP_SYSTEM_USER_ID, $t['issue_id'], array($reference_sup_id));
                }
            }
            // need to check crm for customer association
            if (!empty($email->fromaddress)) {
                $details = Email_Account::getDetails($info['ema_id']);
                if (Customer::hasCustomerIntegration($info['ema_prj_id'])) {
                    // check for any customer contact association in spot
                    list($customer_id,) = Customer::getCustomerIDByEmails($info['ema_prj_id'], array($sender_email));
                    $t['customer_id'] = $customer_id;
                }
            }
            if (empty($t['issue_id'])) {
                $t['issue_id'] = 0;
            }
            $res = Support::insertEmail($t, $structure);
            if ($res != -1) {
                // only extract the attachments from the email if we are associating the email to an issue
                if (!empty($t['issue_id'])) {
                    Support::extractAttachments($t['issue_id'], $message);
                    // if the issue was auto created, don't broadcast the email to the notification list
                    if (($info['ema_issue_auto_creation'] != 'enabled') || (!$should_create_issue)) {
                        // since downloading email should make the emails 'public', send 'false' below as the 'internal_only' flag
                        Notification::notifyNewEmail(APP_SYSTEM_USER_ID, $t["issue_id"], $structure, $message, false);
                    }
                    if (!empty($t['customer_id'])) {
                        Issue::markAsUpdated($t["issue_id"], "customer action");
                    } else {
                        $sender_usr_id = User::getUserIDByEmail($sender_email);
                        if ((!empty($sender_usr_id)) && (User::getRoleByUser($sender_usr_id) > User::getRoleID('Customer'))) {
                            Issue::markAsUpdated($t["issue_id"], "staff response");
                        }
                    }
                }
                // need to delete the message from the server?
                if (!$info['ema_leave_copy']) {
                    @imap_delete($mbox, $num);
                    @imap_expunge($mbox);
                } else {
                    // mark the message as already read
                    @imap_setflag_full($mbox, $num, "\\Seen");
                }
            }
            return true;
        } else {
            return false;
        }
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
                    sup_ema_id=$ema_id";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Checks if a message already is downloaded. If available, the global variable $support_email_message_ids will be used,
     * otherwise the database will be checked directly.
     * 
     * @access  public
     * @param   integer $ema_id The support email account ID
     * @param   string $message_id The Message-ID header
     * @return  boolean
     */
    function exists($ema_id, $message_id)
    {
        static $message_ids;

        // if the static variable doesn't exist, build it
        if (@count($message_ids) == 0) {
            $message_ids = Support::getMessageIDs($ema_id);
        }
        if (in_array($message_id, $message_ids)) {
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
     * @param   object $structure The parsed structure of the email message
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function insertEmail($row, $structure)
    {
        // get usr_id from FROM header
        $usr_id = User::getUserIDByEmail(Mail_API::getEmailAddress($structure->headers['from']));
        if (!empty($usr_id) && !empty($row["customer_id"])) {
            $row["customer_id"] = User::getCustomerID($usr_id);
        }
        if (empty($row['customer_id'])) {
            $row['customer_id'] = "NULL";
        }
        
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 (
                    sup_ema_id,
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
                    " . $row["ema_id"] . ",
                    " . $row["issue_id"] . ",";
        if (!empty($usr_id)) {
            $stmt .= "\n$usr_id,\n";
        }
        $stmt .= "  
                    " . $row["customer_id"] . ",
                    '" . Misc::escapeString($row["message_id"]) . "',
                    '" . Misc::escapeString($row["date"]) . "',
                    '" . Misc::escapeString($row["from"]) . "',
                    '" . Misc::escapeString($row["to"]) . "',
                    '" . Misc::escapeString($row["cc"]) . "',
                    '" . Misc::escapeString($row["subject"]) . "',
                    '" . Misc::escapeString($row["has_attachment"]) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_sup_id = $GLOBALS["db_api"]->get_last_insert_id();
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
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                Workflow::handleNewEmail(Support::getProjectByEmailAccount($row["ema_id"]), @$row["issue_id"], $structure, $row);
                return 1;
            }
        }
    }


    /**
     * Method used to get the current listing related cookie information.
     *
     * @access  public
     * @param   boolean $full_cookie If the full cookie or user specific params should be returned.
     * @return  array The email listing information
     */
    function getCookieParams($full_cookie = false)
    {
        global $HTTP_COOKIE_VARS;

        $cookie = @unserialize(base64_decode($HTTP_COOKIE_VARS[APP_EMAIL_LIST_COOKIE]));
        if ($full_cookie) {
            return $cookie;
        } else {
            return @$cookie[Auth::getUserID()][Auth::getCurrentProject()];
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
        global $HTTP_POST_VARS, $HTTP_GET_VARS;
        $cookie = Support::getCookieParams();

        if (isset($HTTP_GET_VARS[$name])) {
            return $HTTP_GET_VARS[$name];
        } elseif (isset($HTTP_POST_VARS[$name])) {
            return $HTTP_POST_VARS[$name];
        } elseif (isset($cookie[$name])) {
            return $cookie[$name];
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
        $sort_by = Support::getParam('sort_by');
        $sort_order = Support::getParam('sort_order');
        $rows = Support::getParam('rows');
        $cookie = array(
            'rows'             => $rows ? $rows : APP_DEFAULT_PAGER_SIZE,
            'pagerRow'         => Support::getParam('pagerRow'),
            'hide_associated'  => Support::getParam('hide_associated'),
            "sort_by"          => $sort_by ? $sort_by : "sup_date",
            "sort_order"       => $sort_order ? $sort_order : "DESC",
            // quick filter form options
            'keywords'         => Support::getParam('keywords'),
            'sender'           => Support::getParam('sender'),
            'to'               => Support::getParam('to'),
            'ema_id'           => Support::getParam('ema_id'),
            'filter'           => Support::getParam('filter')
        );
        // now do some magic to properly format the date fields
        $date_fields = array(
            'arrival_date'
        );
        foreach ($date_fields as $field_name) {
            $field = Support::getParam($field_name);
            if ((empty($field)) || ($cookie['filter'][$field_name] != 'yes')) {
                continue;
            }
            $end_field_name = $field_name . '_end';
            $end_field = Support::getParam($end_field_name);
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
        // set search information for specific usr_id
        $full_cookie = Support::getCookieParams(true);
        $full_cookie[Auth::getUserID()][Auth::getCurrentProject()] = $cookie;
        $encoded = base64_encode(serialize($full_cookie));
        setcookie(APP_EMAIL_LIST_COOKIE, $encoded, APP_EMAIL_LIST_COOKIE_EXPIRE);
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
        global $HTTP_SERVER_VARS;

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
                $items["links"][$fields[$i]] = $HTTP_SERVER_VARS["PHP_SELF"] . "?sort_by=" . $fields[$i] . "&sort_order=" . $sort_order;
            } else {
                $items["links"][$fields[$i]] = $HTTP_SERVER_VARS["PHP_SELF"] . "?sort_by=" . $fields[$i] . "&sort_order=asc";
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account";
        if (!empty($options['keywords'])) {
            $stmt .= "," . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body";
        }
        $stmt .= Support::buildWhereClause($options);
        $stmt .= "
                 ORDER BY
                    " . $options["sort_by"] . " " . $options["sort_order"];
        $total_rows = Pager::getTotalRows($stmt);
        $stmt .= "
                 LIMIT
                    $start, $max";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array(
                "list" => "",
                "info" => ""
            );
        } else {
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
                $res[$i]["sup_date"] = Date_API::getFormattedDate($res[$i]["sup_date"]);
                $res[$i]["sup_subject"] = Mime_Helper::fixEncoding($res[$i]["sup_subject"]);
                $res[$i]["sup_from"] = Mime_Helper::fixEncoding(Mail_API::getName($res[$i]["sup_from"]));
                if ((empty($res[$i]["sup_to"])) && (!empty($res[$i]["sup_iss_id"]))) {
                    $res[$i]["sup_to"] = "Notification List";
                } else {
                    $res[$i]["sup_to"] = Mime_Helper::fixEncoding(Mail_API::getName($res[$i]["sup_to"]));
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
        return $stmt;
    }


    /**
     * Method used to extract and associate attachments in an email
     * to the given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $full_email The full contents of the email
     * @return  void
     */
    function extractAttachments($issue_id, $full_email)
    {
        // figure out who should be the 'owner' of this attachment
        $structure = Mime_Helper::decode($full_email, false, false);
        $sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));
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
                $unknown_user = $structure->headers['from'];
            }
        }
        // now for the real thing
        $attachments = Mime_Helper::getAttachments($full_email);
        if (count($attachments) > 0) {
            $attachment_id = Attachment::add($issue_id, $usr_id, 'Attachment originated from an email', $unknown_user);
            for ($i = 0; $i < count($attachments); $i++) {
                Attachment::addFile($attachment_id, $issue_id, $attachments[$i]['filename'], $attachments[$i]['filetype'], $attachments[$i]['blob']);
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
                    sup_id IN (" . @implode(", ", $items) . ")";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            for ($i = 0; $i < count($items); $i++) {
                $full_email = Support::getFullEmail($items[$i]);
                Support::extractAttachments($issue_id, $full_email);
            }
            Issue::markAsUpdated($issue_id, "email");
            // save a history entry for each email being associated to this issue
            $stmt = "SELECT
                        sup_subject
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                     WHERE
                        sup_id IN (" . @implode(", ", $items) . ")";
            $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
            for ($i = 0; $i < count($res); $i++) {
                History::add($issue_id, $usr_id, History::getTypeID('email_associated'), 
                       'Email (subject: \'' . $res[$i] . '\') associated by ' . User::getFullName($usr_id));
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
     * @return  integer 1 if it worked, -1 otherwise
     */
    function associate($usr_id, $issue_id, $items)
    {
        $res = Support::associateEmail($usr_id, $issue_id, $items);
        if ($res == 1) {
            $stmt = "SELECT
                        sup_id,
                        seb_full_email
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                     WHERE
                        sup_id=seb_sup_id AND
                        sup_id IN (" . @implode(", ", $items) . ")";
            $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
            for ($i = 0; $i < count($res); $i++) {
                // since downloading email should make the emails 'public', send 'false' below as the 'internal_only' flag
                $structure = Mime_Helper::decode($res[$i]['seb_full_email'], true, false);
                Notification::notifyNewEmail($usr_id, $issue_id, $structure, $res[$i]['seb_full_email'], false);
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
        $stmt = "SELECT
                    " . APP_TABLE_PREFIX . "support_email.*,
                    " . APP_TABLE_PREFIX . "support_email_body.*,
                    UNIX_TIMESTAMP(sup_date) AS timestamp
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                 WHERE
                    sup_id=seb_sup_id AND
                    sup_id=$sup_id AND
                    sup_ema_id=$ema_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // gotta parse MIME based emails now
            $output = Mime_Helper::decode($res["seb_full_email"], true);
            $res["message"] = Mime_Helper::getMessageBody($output); // XXX: check which code relies on this var
            $res["attachments"] = Mime_Helper::getAttachmentCIDs($res["seb_full_email"]);
            $res["sup_date"] = Date_API::getFormattedDate($res["sup_date"]);
            $res["sup_subject"] = Mime_Helper::fixEncoding($res["sup_subject"]);
            // remove extra 'Re: ' from subject
            $res['reply_subject'] = Mail_API::removeExcessRe('Re: ' . $res["sup_subject"]);
            $res["sup_from"] = Mime_Helper::fixEncoding($res["sup_from"]);
            $res["sup_to"] = Mime_Helper::fixEncoding($res["sup_to"]);
            
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
                    sup_iss_id = $issue_id
                ORDER BY
                    sup_id
                LIMIT " . ($sequence - 1) . ", 1";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else if (count($res) < 1) {
            return array();
        } else {
            return Support::getEmailDetails($res[1], $res[0]);
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
        $items = @implode(", ", $items);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
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
                    seb_sup_id=$sup_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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
        $stmt = "SELECT
                    sup_id,
                    sup_ema_id,
                    sup_from,
                    sup_to,
                    sup_cc,
                    sup_date,
                    sup_subject,
                    seb_body,
                    sup_has_attachment,
                    CONCAT(sup_ema_id, '-', sup_id) AS composite_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    sup_id=seb_sup_id AND
                    ema_id=sup_ema_id AND
                    iss_id = sup_iss_id AND
                    ema_prj_id=iss_prj_id AND
                    sup_iss_id=$issue_id
                 ORDER BY
                    sup_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (count($res) == 0) {
                return "";
            } else {
                for ($i = 0; $i < count($res); $i++) {
                    $res[$i]["sup_date"] = Date_API::getFormattedDate($res[$i]["sup_date"]);
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
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["item"]);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_removed=1
                 WHERE
                    sup_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["item"]);
        $stmt = "SELECT
                    sup_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id IN ($items)";
        $issue_id = $GLOBALS["db_api"]->dbh->getOne($stmt);

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_iss_id=0
                 WHERE
                    sup_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
            $subjects = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
            for ($i = 0; $i < count($HTTP_POST_VARS["item"]); $i++) {
                History::add($issue_id, Auth::getUserID(), History::getTypeID('email_disassociated'), 
                                'Email (subject: \'' . $subjects[$HTTP_POST_VARS["item"][$i]] . '\') disassociated by ' . User::getFullName(Auth::getUserID()));
            }
            return 1;
        }
    }


    /**
     * Method used to get the appropriate Message-ID header for a 
     * given issue.
     *
     * @access  public
     * @return  string The Message-ID header
     */
    function getMessageID()
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ((float)$usec + (float)$sec);
        $first = base_convert($time, 10, 36);
        mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);
        $rand = mt_rand();
        $second = base_convert($rand, 10, 36);
        return "<eventum." . $first . "." . $second . "@" . APP_HOSTNAME . ">";
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
    function isAllowedToEmail($issue_id, $sender_email)
    {
        $is_allowed = true;
        $sender_usr_id = User::getUserIDByEmail($sender_email);
        if (empty($sender_usr_id)) {
            $prj_id = Issue::getProjectID($issue_id);
            if (Customer::hasCustomerIntegration($prj_id)) {
                // check for a customer contact with several email addresses
                $customer_id = Issue::getCustomerID($issue_id);
                $contact_emails = array_keys(Customer::getContactEmailAssocList($prj_id, $customer_id));
                if ((!in_array($sender_email, $contact_emails)) &&
                        (!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email))) {
                    $is_allowed = false;
                }
            }
        } else {
            // check if this user is not a customer and 
            // also not in the assignment list for the current issue and
            // also not in the authorized repliers list
            if ((!Authorized_Replier::isUserAuthorizedReplier($issue_id, $sender_usr_id)) &&
                    (!Issue::isAssignedToUser($issue_id, $sender_usr_id)) &&
                    (User::getRoleByUser($sender_usr_id) != User::getRoleID('Customer'))) {
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
     * @return  string The full email
     */
    function buildFullHeaders($issue_id, $message_id, $from, $to, $cc, $subject, $body, $in_reply_to)
    {
        // hack needed to get the full headers of this web-based email
        $mail = new Mail_API;
        $mail->setTextBody($body);
        if (!empty($issue_id)) {
            $mail->setHeaders(array("Message-Id" => $message_id));
        } else {
            $issue_id = 0;
        }
        if ($in_reply_to) {
            $mail->setHeaders(array("In-Reply-To" => $in_reply_to));
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
     * @return  void
     */
    function sendDirectEmail($issue_id, $from, $to, $cc, $subject, $body, $message_id)
    {
        $recipients = Support::getRecipientsCC($cc);
        $recipients[] = $to;
        // send the emails now, one at a time
        foreach ($recipients as $recipient) {
            $mail = new Mail_API;
            if (!empty($issue_id)) {
                // add the warning message to the current message' body, if needed
                $fixed_body = Mail_API::addWarningMessage($issue_id, $recipient, $body);
                $mail->setHeaders(array(
                    "Message-Id" => $message_id
                ));
            } else {
                $fixed_body = $body;
            }
            $mail->setTextBody($fixed_body);
            $mail->send($from, $recipient, $subject, TRUE, $issue_id);
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
        global $HTTP_POST_VARS, $HTTP_SERVER_VARS;

        // if we are replying to an existing email, set the In-Reply-To: header accordingly
        if ($parent_sup_id) {
            $in_reply_to = Support::getMessageIDByID($parent_sup_id);
        } else {
            $in_reply_to = false;
        }
        // remove extra 'Re: ' from subject
        $HTTP_POST_VARS['subject'] = Mail_API::removeExcessRe($HTTP_POST_VARS['subject']);
        $internal_only = false;
        $message_id = Support::getMessageID();
        // hack needed to get the full headers of this web-based email
        $full_email = Support::buildFullHeaders($HTTP_POST_VARS["issue_id"], $message_id, $HTTP_POST_VARS["from"],
                $HTTP_POST_VARS["to"], $HTTP_POST_VARS["cc"], $HTTP_POST_VARS["subject"], $HTTP_POST_VARS["message"], $in_reply_to);

        // email blocking should only be done if this is an email about an associated issue
        if (!empty($HTTP_POST_VARS['issue_id'])) {
            $user_info = User::getNameEmail(Auth::getUserID());
            // check whether the current user is allowed to send this email to customers or not
            if (!Support::isAllowedToEmail($HTTP_POST_VARS["issue_id"], $user_info['usr_email'])) {
                // add the message body as a note
                $HTTP_POST_VARS['blocked_msg'] = $full_email;
                $HTTP_POST_VARS['title'] = $HTTP_POST_VARS["subject"];
                $HTTP_POST_VARS['note'] = Mail_API::getCannedBlockedMsgExplanation() . $HTTP_POST_VARS["message"];
                Note::insert(Auth::getUserID(), $HTTP_POST_VARS["issue_id"]);
                Workflow::handleBlockedEmail(Issue::getProjectID($HTTP_POST_VARS['issue_id']), $HTTP_POST_VARS['issue_id'], $HTTP_POST_VARS, 'web');
                return 1;
            }
        }

        // only send a direct email if the user doesn't want to add the Cc'ed people to the notification list
        if (@$HTTP_POST_VARS['add_unknown'] == 'yes') {
            if (!empty($HTTP_POST_VARS['issue_id'])) {
                // add the recipients to the notification list of the associated issue
                $recipients = array($HTTP_POST_VARS['to']);
                $recipients = array_merge($recipients, Support::getRecipientsCC($HTTP_POST_VARS['cc']));
                for ($i = 0; $i < count($recipients); $i++) {
                    if ((!empty($recipients[$i])) && (!Notification::isIssueRoutingSender($HTTP_POST_VARS["issue_id"], $recipients[$i]))) {
                        Notification::subscribeEmail(Auth::getUserID(), $HTTP_POST_VARS["issue_id"], Mail_API::getEmailAddress($recipients[$i]), array('emails'));
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
            if (!empty($HTTP_POST_VARS['issue_id'])) {
                // send direct emails only to the unknown addresses, and leave the rest to be
                // catched by the notification list
                $from = Notification::getFixedFromHeader($HTTP_POST_VARS['issue_id'], $HTTP_POST_VARS['from'], 'issue');
                // build the list of unknown recipients
                if (!empty($HTTP_POST_VARS['to'])) {
                    $recipients = array($HTTP_POST_VARS['to']);
                    $recipients = array_merge($recipients, Support::getRecipientsCC($HTTP_POST_VARS['cc']));
                } else {
                    $recipients = Support::getRecipientsCC($HTTP_POST_VARS['cc']);
                }
                $unknowns = array();
                for ($i = 0; $i < count($recipients); $i++) {
                    if (!Notification::isSubscribedToEmails($HTTP_POST_VARS['issue_id'], $recipients[$i])) {
                        $unknowns[] = $recipients[$i];
                    }
                }
                if (count($unknowns) > 0) {
                    $to = array_shift($unknowns);
                    $cc = implode('; ', $unknowns);
                    // send direct emails
                    Support::sendDirectEmail($HTTP_POST_VARS['issue_id'], $from, $to, $cc,
                            $HTTP_POST_VARS['subject'], $HTTP_POST_VARS['message'], $message_id);
                }
            } else {
                // send direct emails to all recipients, since we don't have an associated issue
                $project_info = Project::getOutgoingSenderAddress(Auth::getCurrentProject());
                // use the project-related outgoing email address, if there is one
                if (!empty($project_info['email'])) {
                    $from = Mail_API::getFormattedName(User::getFullName(Auth::getUserID()), $project_info['email']);
                } else {
                    // otherwise, use the real email address for the current user
                    $from = User::getFromHeader(Auth::getUserID());
                }
                // send direct emails
                Support::sendDirectEmail($HTTP_POST_VARS['issue_id'], $from, $HTTP_POST_VARS['to'], $HTTP_POST_VARS['cc'],
                        $HTTP_POST_VARS['subject'], $HTTP_POST_VARS['message'], $message_id);
            }
        }

        $t = array(
            'customer_id'    => 'NULL',
            'issue_id'       => $HTTP_POST_VARS["issue_id"] ? $HTTP_POST_VARS["issue_id"] : 0,
            'ema_id'         => $HTTP_POST_VARS['ema_id'],
            'message_id'     => $message_id,
            'date'           => Date_API::getCurrentDateGMT(),
            'from'           => $HTTP_POST_VARS['from'],
            'to'             => $HTTP_POST_VARS['to'],
            'cc'             => @$HTTP_POST_VARS['cc'],
            'subject'        => @$HTTP_POST_VARS['subject'],
            'body'           => $HTTP_POST_VARS['message'],
            'full_email'     => $full_email,
            'has_attachment' => 0
        );
        // associate this new email with a customer, if appropriate
        if (User::getRoleByUser(Auth::getUserID()) == User::getRoleID('Customer')) {
            $customer_id = User::getCustomerID(Auth::getUserID());
            if ((empty($customer_id)) && ($customer_id != -1)) {
                $t['customer_id'] = $customer_id;
            }
        }
        $structure = Mime_Helper::decode($full_email, true, false);
        $res = Support::insertEmail($t, $structure);
        if (!empty($HTTP_POST_VARS["issue_id"])) {
            // need to send a notification
            Notification::notifyNewEmail(Auth::getUserID(), $HTTP_POST_VARS["issue_id"], $structure, $full_email, $internal_only);
            // mark this issue as updated
            if (!empty($t['customer_id'])) {
                Issue::markAsUpdated($HTTP_POST_VARS["issue_id"], 'customer action');
            } else {
                Issue::markAsUpdated($HTTP_POST_VARS["issue_id"], 'staff response');
            }
            // save a history entry for this
            History::add($HTTP_POST_VARS["issue_id"], Auth::getUserID(), History::getTypeID('email_sent'), 
                            'Outgoing email sent by ' . User::getFullName(Auth::getUserID()));

            // also update the last_response_date field for the associated issue
            if (User::getRoleByUser(Auth::getUserID()) > User::getRoleID('Customer')) {
                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                         SET
                            iss_last_response_date='" . Date_API::getCurrentDateGMT() . "'
                         WHERE
                            iss_id=" . $HTTP_POST_VARS["issue_id"];
                $GLOBALS["db_api"]->dbh->query($stmt);

                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                         SET
                            iss_first_response_date='" . Date_API::getCurrentDateGMT() . "'
                         WHERE
                            iss_first_response_date IS NULL AND
                            iss_id=" . $HTTP_POST_VARS["issue_id"];
                $GLOBALS["db_api"]->dbh->query($stmt);
            }
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
                    sup_id=$sup_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the email ID associated with a given support
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
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
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
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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
                    sup_id=$sup_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_date BETWEEN '$start' AND '$end' AND
                    sup_from LIKE '%" . $usr_info["usr_email"] . "%' AND
                    sup_iss_id ";
        if ($associated == true) {
            $stmt .= "!= 0";
        } else {
            $stmt .= "= 0";
        }
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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
     * @return  iteger The ID of the of the project.
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
                    ema_id = $ema_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        $returns[$ema_id] = $res;
        return $res;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Support Class');
}
?>