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

class Support
{
    // XXX: put documentation here
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
                    sup_iss_id=$issue_id";
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
        $fp = fopen($path . $filename, 'w');
        fwrite($fp, $message);
        fclose($fp);
        chmod($path . $filename, 0777);
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
     * Method used to get the account ID for a given email account.
     *
     * @access  public
     * @param   string $username The username for the specific email account
     * @param   string $hostname The hostname for the specific email account
     * @param   string $mailbox The mailbox for the specific email account
     * @return  integer The support email account ID
     */
    function getAccountID($username, $hostname, $mailbox)
    {
        $stmt = "SELECT
                    ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_username='" . addslashes($username) . "' AND
                    ema_hostname='" . addslashes($hostname) . "' AND
                    ema_folder='" . addslashes($mailbox) . "'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } else {
            if ($res == NULL) {
                return 0;
            } else {
                return $res;
            }
        }
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
                $res[$i]["sup_subject"] = Support::fixEncoding($res[$i]["sup_subject"]);
                $res[$i]["sup_from"] = Support::fixEncoding($res[$i]["sup_from"]);
            }
            return $res;
        }
    }


    /**
     * Method used to get the details of a given support email 
     * account.
     *
     * @access  public
     * @param   integer $ema_id The support email account ID
     * @return  array The account details
     */
    function getDetails($ema_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id=$ema_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to remove all support email accounts associated 
     * with a specified set of projects.
     *
     * @access  public
     * @param   array $ids The list of projects
     * @return  boolean
     */
    function removeAccountByProjects($ids)
    {
        $items = @implode(", ", $ids);
        $stmt = "SELECT
                    ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_prj_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Support::removeEmailByAccounts($res);
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                     WHERE
                        ema_prj_id IN ($items)";
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } else {
                return true;
            }
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
     * Method used to remove the specified support email accounts.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Support::removeEmailByAccounts($HTTP_POST_VARS["items"]);
            return true;
        }
    }


    /**
     * Method used to add a new support email account.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        if (empty($HTTP_POST_VARS["get_only_new"])) {
            $HTTP_POST_VARS["get_only_new"] = 0;
        }
        if (empty($HTTP_POST_VARS["leave_copy"])) {
            $HTTP_POST_VARS["leave_copy"] = 0;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 (
                    ema_prj_id,
                    ema_type,
                    ema_hostname,
                    ema_port,
                    ema_folder,
                    ema_username,
                    ema_password,
                    ema_get_only_new,
                    ema_leave_copy
                 ) VALUES (
                    " . $HTTP_POST_VARS["project"] . ",
                    '" . Misc::runSlashes($HTTP_POST_VARS["type"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["hostname"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["port"]) . "',
                    '" . Misc::runSlashes(@$HTTP_POST_VARS["folder"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["username"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["password"]) . "',
                    " . $HTTP_POST_VARS["get_only_new"] . ",
                    " . $HTTP_POST_VARS["leave_copy"] . "
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to update a support email account details.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
    {
        global $HTTP_POST_VARS;

        if (empty($HTTP_POST_VARS["get_only_new"])) {
            $HTTP_POST_VARS["get_only_new"] = 0;
        }
        if (empty($HTTP_POST_VARS["leave_copy"])) {
            $HTTP_POST_VARS["leave_copy"] = 0;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 SET
                    ema_prj_id=" . $HTTP_POST_VARS["project"] . ",
                    ema_type='" . Misc::runSlashes($HTTP_POST_VARS["type"]) . "',
                    ema_hostname='" . Misc::runSlashes($HTTP_POST_VARS["hostname"]) . "',
                    ema_port='" . Misc::runSlashes($HTTP_POST_VARS["port"]) . "',
                    ema_folder='" . Misc::runSlashes(@$HTTP_POST_VARS["folder"]) . "',
                    ema_username='" . Misc::runSlashes($HTTP_POST_VARS["username"]) . "',
                    ema_password='" . Misc::runSlashes($HTTP_POST_VARS["password"]) . "',
                    ema_get_only_new=" . $HTTP_POST_VARS["get_only_new"] . ",
                    ema_leave_copy=" . $HTTP_POST_VARS["leave_copy"] . "
                 WHERE
                    ema_id=" . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the list of available support email 
     * accounts in the system.
     *
     * @access  public
     * @return  array The list of accounts
     */
    function getList()
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 ORDER BY
                    ema_hostname";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["prj_title"] = Project::getName($res[$i]["ema_prj_id"]);
            }
            return $res;
        }
    }


    /**
     * Method used to get an associative array of the support email
     * accounts in the format of account ID => account title.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of accounts
     */
    function getAssocList($prj_id)
    {
        $stmt = "SELECT
                    ema_id,
                    CONCAT(ema_username, '@', ema_hostname, ' ', ema_folder) AS ema_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_prj_id=$prj_id
                 ORDER BY
                    ema_title";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
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
                return '';
            }
        }
        $email = @imap_headerinfo($mbox, $num);
        // we can't trust the in-reply-to from the imap c-client, so let's
        // try to manually parse that value from the full headers
        if (empty($email->in_reply_to)) {
            $headers = @imap_fetchheader($mbox, $num);
            if (preg_match("/^in-reply-to:(.*)/i", $headers, $matches)) {
                $email->in_reply_to = trim($matches[1]);
            }
        }
        if (!Support::exists($info['ema_id'], @$email->message_id)) {
            $body = @imap_body($mbox, $num);
            $message = @imap_fetchheader($mbox, $num) . $body;
            // check for mysterious blank messages
            if (empty($message)) {
                return '';
            }
            $parts = array();
            $output = Mime_Helper::decode($message);
            Mime_Helper::parse_output($output, $parts);
            if (@count($parts["attachments"]) > 0) {
                $has_attachments = 1;
            } else {
                $has_attachments = 0;
            }

            $t = array(
                'ema_id'         => $info['ema_id'],
                'message_id'     => @addslashes($email->message_id),
                'date'           => @addslashes(Date_API::getDateGMTByTS($email->udate)),
                'from'           => @addslashes($email->fromaddress),
                'to'             => @addslashes($email->toaddress),
                'cc'             => @addslashes($email->ccaddress),
                'subject'        => @addslashes($email->subject),
                'body'           => @addslashes($body),
                'full_email'     => @addslashes($message),
                'has_attachment' => $has_attachments
            );
            if (!empty($email->in_reply_to)) {
                $issue_id = preg_replace("'<issue_(\d+)@.*>'", "\\1", $email->in_reply_to);
                if (is_numeric($issue_id)) {
                    $t['issue_id'] = $issue_id;
                } else {
                    $t['issue_id'] = 0;
                }
            } else {
                $t['issue_id'] = 0;
            }
            $structure = Mime_Helper::decode($message, true, false);
            $res = Support::insertEmail($t, $structure);
            if ($res != -1) {
                // only extract the attachments from the email if we are associating the email to an issue
                if (!empty($t['issue_id'])) {
                    Support::extractAttachments($t['issue_id'], $message);
                    // since downloading email should make the emails 'public', send 'false' below as the 'internal_only' flag
                    Notification::notifyNewEmail($t["issue_id"], $structure, $message, false);
                    Issue::markAsUpdated($t["issue_id"]);
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
            return $t;
        } else {
            $t = "'" . @$email->subject . "'";
            if (!@empty($email->fromaddress)) {
                $t .= " from " . trim(@$email->fromaddress);
            }
            return $t;
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
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 (
                    sup_ema_id,
                    sup_iss_id,
                    sup_message_id,
                    sup_date,
                    sup_from,
                    sup_to,
                    sup_cc,
                    sup_subject,
                    sup_body,
                    sup_full_email,
                    sup_has_attachment
                 ) VALUES (
                    " . $row["ema_id"] . ",
                    " . $row["issue_id"] . ",
                    '" . $row["message_id"] . "',
                    '" . $row["date"] . "',
                    '" . $row["from"] . "',
                    '" . $row["to"] . "',
                    '" . $row["cc"] . "',
                    '" . $row["subject"] . "',
                    '" . $row["body"] . "',
                    '" . $row["full_email"] . "',
                    '" . $row["has_attachment"] . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the current listing related cookie information.
     *
     * @access  public
     * @return  array The email listing information
     */
    function getCookieParams()
    {
        global $HTTP_COOKIE_VARS;
        return @unserialize(base64_decode($HTTP_COOKIE_VARS[APP_EMAIL_LIST_COOKIE]));
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
        $encoded = base64_encode(serialize($cookie));
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
        if ($max == "ALL") {
            $max = 9999999;
        }
        $start = $current_row * $max;

        $stmt = "SELECT
                    sup_id,
                    sup_ema_id,
                    sup_iss_id,
                    sup_from,
                    sup_date,
                    sup_to,
                    sup_subject,
                    sup_has_attachment
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account";
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
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["sup_date"] = Date_API::getFormattedDate($res[$i]["sup_date"]);
                $res[$i]["sup_subject"] = Support::fixEncoding($res[$i]["sup_subject"]);
                $res[$i]["sup_from"] = Support::fixEncoding($res[$i]["sup_from"]);
                $res[$i]["sup_to"] = Support::fixEncoding($res[$i]["sup_to"]);
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
            $stmt .= " AND (" . Misc::prepareBooleanSearch('sup_subject', Misc::runSlashes($options["keywords"]));
            $stmt .= " OR " . Misc::prepareBooleanSearch('sup_body', Misc::runSlashes($options["keywords"])) . ")";
        }
        if (!empty($options['sender'])) {
            $stmt .= " AND " . Misc::prepareBooleanSearch('sup_from', Misc::runSlashes($options["sender"]));
        }
        if (!empty($options['to'])) {
            $stmt .= " AND " . Misc::prepareBooleanSearch('sup_to', Misc::runSlashes($options["to"]));
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
     * Method used to fix the encoding of MIME based strings.
     *
     * @access  public
     * @param   string $input The string to be fixed
     * @return  string The fixed string
     */
    function fixEncoding($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);
        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {
            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];
            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;
                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }
            $input = str_replace($encoded, $text, $input);
        }
        return $input;
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
        if (empty($usr_id)) {
            // if we couldn't find a real user by that email, just use the first issue assignee as the owner
            $users = Issue::getAssignedUserIDs($issue_id);
            if (count($users) > 0) {
                $usr_id = $users[0];
            } else {
                // if we can't find any reasonable owner for this attachment, just use the current user
                $usr_id = Auth::getUserID();
            }
        }
        // now for the real thing
        $attachments = Mime_Helper::getAttachments($full_email);
        if (count($attachments) > 0) {
            $attachment_id = Attachment::add($issue_id, $usr_id, 'Attachment originated from a support email');
            for ($i = 0; $i < count($attachments); $i++) {
                Attachment::addFile($attachment_id, $issue_id, $attachments[$i]['filename'], $attachments[$i]['filetype'], $attachments[$i]['blob']);
            }
        }
    }


    /**
     * Method used to associate a support email with an existing 
     * issue.
     *
     * @access  public
     * @return  integer 1 if it worked, -1 otherwise
     */
    function associate()
    {
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["item"]);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_iss_id=" . $HTTP_POST_VARS["issue"] . "
                 WHERE
                    sup_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            for ($i = 0; $i < count($HTTP_POST_VARS["item"]); $i++) {
                $full_email = Support::getFullEmail($HTTP_POST_VARS["item"][$i]);
                Support::extractAttachments($HTTP_POST_VARS['issue'], $full_email);
            }
            Issue::markAsUpdated($HTTP_POST_VARS["issue"]);
            // save a history entry for each email being associated to this issue
            $stmt = "SELECT
                        sup_id,
                        sup_subject,
                        sup_full_email
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                     WHERE
                        sup_id IN ($items)";
            $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
            for ($i = 0; $i < count($res); $i++) {
                History::add($HTTP_POST_VARS["issue"], 'Support email (subject: \'' . $res[$i]['sup_subject'] . '\') associated.');
                // send notifications for the issue being updated
                // since downloading email should make the emails 'public', send 'false' below as the 'internal_only' flag
                $structure = Mime_Helper::decode($res[$i]['sup_full_email'], true, false);
                Notification::notifyNewEmail($HTTP_POST_VARS["issue"], $structure, $res[$i]['sup_full_email'], false);
            }
            return 1;
        }
    }


    // XXX: put documentation here
    function getMessageBody(&$output)
    {
        $parts = array();
        Mime_Helper::parse_output($output, $parts);
        if (isset($parts["text"])) {
            return $parts["text"][0];
        } elseif (isset($parts["html"])) {
            return $parts["html"][0];
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
                    *,
                    UNIX_TIMESTAMP(sup_date) AS timestamp
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id=$sup_id AND
                    sup_ema_id=$ema_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // gotta parse MIME based emails now
            $output = Mime_Helper::decode($res["sup_full_email"], true);
            $res["message"] = Support::getMessageBody($output);
            $res["attachments"] = array();
            // now get any eventual attachments
            for ($i = 0; $i < @count($output->parts); $i++) {
                // hack in order to display in-line images from Outlook
                if ((@$output->parts[$i]->ctype_primary == 'image') &&
                        (@$output->parts[$i]->ctype_secondary == 'bmp')) {
                    $res["attachments"][] = array(
                        'filename' => $output->parts[$i]->ctype_parameters['name'],
                        'cid'      => $output->parts[$i]->headers['content-id']
                    );
                    continue;
                }
                if (@$output->parts[$i]->disposition == 'attachment') {
                    $res["attachments"][] = array(
                        'filename' => $output->parts[$i]->d_parameters["filename"]
                    );
                    continue;
                }
            }
            $res["sup_date"] = Date_API::getFormattedDate($res["sup_date"]);
            $res["sup_subject"] = Support::fixEncoding($res["sup_subject"]);
            $res["sup_from"] = Support::fixEncoding($res["sup_from"]);
            $res["sup_to"] = Support::fixEncoding($res["sup_to"]);
            return $res;
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
                $res[$i]["sup_subject"] = Support::fixEncoding($res[$i]["sup_subject"]);
                $res[$i]["sup_from"] = Support::fixEncoding($res[$i]["sup_from"]);
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
                    sup_full_email
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
                    sup_date,
                    sup_subject,
                    sup_has_attachment
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id=sup_ema_id AND
                    ema_prj_id=" . Auth::getCurrentProject() . " AND
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
                    $res[$i]["sup_subject"] = Support::fixEncoding($res[$i]["sup_subject"]);
                    $res[$i]["sup_from"] = Support::fixEncoding($res[$i]["sup_from"]);
                    $res[$i]["sup_to"] = Support::fixEncoding($res[$i]["sup_to"]);
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
                History::add($issue_id, 'Support email (subject: \'' . $subjects[$HTTP_POST_VARS["item"][$i]] . '\') disassociated.');
            }
            return 1;
        }
    }


    /**
     * Method used to get the appropriate Message-ID header for a 
     * given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  string The Message-ID header
     */
    function getMessageID($issue_id)
    {
        return "<issue_" . $issue_id . "@" . APP_HOSTNAME . ">";
    }


    /**
     * Method used to send an email from the user interface.
     *
     * @access  public
     * @return  integer 1 if it worked, -1 otherwise
     */
    function sendEmail()
    {
        global $HTTP_POST_VARS, $HTTP_SERVER_VARS;

        $internal_only = false;
        // XXX: there should be a better way... maybe just looking at the References:/In-Reply-To: headers and 
        // XXX: then automatically associating the new email to the existing message that is attached to an issue
        $message_id = Support::getMessageID($HTTP_POST_VARS["issue_id"]);
        // hack needed to get the full headers of this web-based email
        $mail = new Mail_API;
        $mail->setTextBody($HTTP_POST_VARS["message"]);
        if (!empty($HTTP_POST_VARS["issue_id"])) {
            $mail->setHeaders(array(
                "Message-Id" => $message_id
            ));
        } else {
            $HTTP_POST_VARS['issue_id'] = 0;
        }
        $HTTP_POST_VARS["cc"] = trim($HTTP_POST_VARS["cc"]);
        if (!empty($HTTP_POST_VARS["cc"])) {
            $cc = str_replace(",", ";", $HTTP_POST_VARS["cc"]);
            $ccs = explode(";", $cc);
            for ($i = 0; $i < count($ccs); $i++) {
                if (!empty($ccs[$i])) {
                    $mail->addCc($ccs[$i]);
                }
            }
        }
        $full_email = $mail->getFullHeaders($HTTP_POST_VARS['from'], $HTTP_POST_VARS['to'], $HTTP_POST_VARS['subject']);

        // only send a direct email if the user doesn't want to add the Cc'ed people to the notification list
        if (@$HTTP_POST_VARS['add_unknown'] == 'yes') {
            // add these people to the notification list
            $ccs[] = $HTTP_POST_VARS['to'];
            for ($i = 0; $i < count($ccs); $i++) {
                if (!Notification::isIssueRoutingSender($HTTP_POST_VARS["issue_id"], $ccs[$i])) {
                    Notification::manualInsert($HTTP_POST_VARS["issue_id"], Mail_API::getEmailAddress($ccs[$i]), array('emails'));
                }
            }
        } else {
            // send direct emails
            unset($mail);

            $from = Notification::getFixedFromHeader($HTTP_POST_VARS['issue_id'], $HTTP_POST_VARS['from'], 'issue');
            $ccs = str_replace(",", ";", $HTTP_POST_VARS["cc"]);
            $ccs = explode(";", $ccs);
            $ccs[] = $HTTP_POST_VARS['to'];
            for ($i = 0; $i < count($ccs); $i++) {
                if ((!empty($ccs[$i])) && (!Notification::isSubscribedToEmails($HTTP_POST_VARS['issue_id'], $ccs[$i]))) {
                    // fix double quoting...
                    if (@get_magic_quotes_gpc() == 1) {
                        $full_message = stripslashes($HTTP_POST_VARS["message"]);
                        $subject = stripslashes($HTTP_POST_VARS["subject"]);
                    } else {
                        $full_message = $HTTP_POST_VARS["message"];
                        $subject = $HTTP_POST_VARS["subject"];
                    }
                    $mail = new Mail_API;
                    $mail->setTextBody($full_message);
                    if (!empty($HTTP_POST_VARS["issue_id"])) {
                        $mail->setHeaders(array(
                            "Message-Id" => $message_id
                        ));
                    }
                    $res = $mail->send($from, $ccs[$i], $subject, TRUE);
                }
            }
        }

        $t = array(
            'issue_id'       => $HTTP_POST_VARS["issue_id"],
            'ema_id'         => $HTTP_POST_VARS['ema_id'],
            'message_id'     => Misc::runSlashes($message_id),
            'date'           => Date_API::getCurrentDateGMT(),
            'from'           => Misc::runSlashes($HTTP_POST_VARS['from']),
            'to'             => Misc::runSlashes($HTTP_POST_VARS['to']),
            'cc'             => @Misc::runSlashes($HTTP_POST_VARS['cc']),
            'subject'        => @Misc::runSlashes($HTTP_POST_VARS['subject']),
            'body'           => Misc::runSlashes($HTTP_POST_VARS['message']),
            'full_email'     => Misc::runSlashes($full_email),
            'has_attachment' => 0
        );
        $structure = Mime_Helper::decode($full_email, true, false);
        $res = Support::insertEmail($t, $structure);
        // strip any slashes that may have been added by PHP when the form was posted...
        if (@get_magic_quotes_gpc() == 1) {
            $full_email = stripslashes($full_email);
            $structure->headers = Misc::array_map_deep($structure->headers, "stripslashes");
        }
        // need to send a notification
        Notification::notifyNewEmail($HTTP_POST_VARS["issue_id"], $structure, $full_email, $internal_only);
        // mark this issue as updated
        Issue::markAsUpdated($HTTP_POST_VARS["issue_id"]);
        // save a history entry for this
        History::add($HTTP_POST_VARS["issue_id"], 'Outgoing email sent by ' . User::getFullName(Auth::getUserID()));

        $usr_id = Auth::getUserID();
        // also update the last_response_date field for the associated issue
        if ((!empty($HTTP_POST_VARS["issue_id"])) && (User::getRoleByUser($usr_id) > User::getRoleID('Reporter'))) {
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

        return 1;
    }


    /**
     * Method used to get the first support email account associated
     * with the current activated project.
     *
     * @access  public
     * @return  integer The email account ID
     */
    function getEmailAccount()
    {
        $stmt = "SELECT
                    ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_prj_id=" . Auth::getCurrentProject() . "
                 LIMIT
                    0, 1";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the support email account associated with a given
     * support email message.
     *
     * @access  public
     * @param   integer $sup_id The support email ID
     * @return  integer The email account ID
     */
    function getAccountByEmail($sup_id)
    {
        $stmt = "SELECT
                    sup_ema_id
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
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Support Class');
}
?>