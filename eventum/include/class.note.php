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
// @(#) $Id: s.class.note.php 1.20 03/12/31 17:29:01-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to adding, updating or
 * deleting notes from the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.validation.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.draft.php");
include_once(APP_INC_PATH . "class.authorized_replier.php");


class Note
{
    /**
     * Returns the next and previous notes associated with the given issue ID 
     * and the currently selected note.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $not_id The currently selected note ID
     * @return  array The next and previous note ID
     */
    function getSideLinks($issue_id, $not_id)
    {
        $stmt = "SELECT
                    not_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_iss_id=$issue_id
                 ORDER BY
                    not_created_date ASC";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // COMPAT: the next line requires PHP >= 4.0.5
            $index = array_search($not_id, $res);
            if (!empty($res[$index+1])) {
                $next = $res[$index+1];
            }
            if (!empty($res[$index-1])) {
                $previous = $res[$index-1];
            }
            return array(
                "next"     => @$next,
                "previous" => @$previous
            );
        }
    }


    /**
     * Retrieves the details about a given note.
     *
     * @access  public
     * @param   integer $note_id The note ID
     * @return  array The note details
     */
    function getDetails($note_id)
    {
        $stmt = "SELECT
                    " . APP_TABLE_PREFIX . "note.*,
                    UNIX_TIMESTAMP(not_created_date) timestamp,
                    not_blocked_message,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    not_usr_id=usr_id AND
                    not_id='$note_id'";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            if (count($res) > 0) {
                $res['not_created_date'] = Date_API::getFormattedDate($res['not_created_date']);
                if (!empty($res['not_blocked_message'])) {
                    $res['has_blocked_message'] = true;
                    $res["attachments"] = Mime_Helper::getAttachmentCIDs($res['not_blocked_message']);
                } else {
                    $res['has_blocked_message'] = false;
                }
                if (!empty($res["not_unknown_user"])) {
                    $res["not_from"] = $res["not_unknown_user"];
                } else {
                    $res["not_from"] = User::getFullName($res['not_usr_id']);
                }
                return $res;
            } else {
                return '';
            }
        }
    }


    /**
     * Returns the blocked email message body associated with the given note ID.
     *
     * @access  public
     * @param   integer $note_id The note ID
     * @return  string The blocked email message body
     */
    function getBlockedMessage($note_id)
    {
        $stmt = "SELECT
                    not_blocked_message
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_id=$note_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Returns the issue ID associated with the given note ID.
     *
     * @access  public
     * @param   integer $note_id The note ID
     * @return  integer The issue ID
     */
    function getIssueID($note_id)
    {
        $stmt = "SELECT
                    not_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_id=$note_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Returns the nth note for the specific issue. Sequence starts at 1.
     * 
     * @access  public
     * @param   integer $issue_id The id of the issue.
     * @param   integer $sequence The sequential number of the note.
     * @return  array An array of data containing details about the note.
     */
    function getNoteBySequence($issue_id, $sequence)
    {
        $stmt = "SELECT
                    not_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                WHERE
                    not_iss_id = $issue_id
                 ORDER BY
                    not_created_date ASC
                LIMIT " . ($sequence - 1) . ", 1";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return Note::getDetails($res);
        }
    }


    /**
     * Method used to get the unknown_user from the note table for the specified note id.
     * 
     * @access  public
     * @param   integer $note_id The note ID
     */
    function getUnknownUser($note_id)
    {
        $sql = "SELECT
                    not_unknown_user
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_id=$note_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to save the routed note into a backup directory.
     *
     * @access  public
     * @param   string $message The full body of the note
     */
    function saveRoutedNote($message)
    {
        $path = APP_PATH . "misc/routed_notes/";
        list($usec,) = explode(" ", microtime());
        $filename = date('dmY.His.') . $usec . '.note.txt';
        $fp = @fopen($path . $filename, 'w');
        @fwrite($fp, $message);
        @fclose($fp);
        @chmod($path . $filename, 0777);
    }


    /**
     * Method used to add a note using the user interface form 
     * available in the application.
     *
     * @param   integer $usr_id The user ID
     * @param   integer $issue_id The issue ID
     * @param   string  $unknown_user The email address of a user that sent the blocked email that was turned into this note. Default is false.
     * @param   boolean $log If adding this note should be logged. Default true.
     * @param   boolean $closing If The issue is being closed. Default false
     * @access  public
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    function insert($usr_id, $issue_id, $unknown_user = FALSE, $log = true, $closing = false)
    {
        global $HTTP_POST_VARS;

        if (@$HTTP_POST_VARS['add_extra_recipients'] != 'yes') {
            $note_cc = array();
        } else {
            $note_cc = $HTTP_POST_VARS['note_cc'];
        }
        // add the poster to the list of people to be subscribed to the notification list
        // only if there is no 'unknown user'.
        $note_cc[] = $usr_id;
        if ($unknown_user == false) {
            for ($i = 0; $i < count($note_cc); $i++) {
                Notification::subscribeUser($usr_id, $issue_id, $note_cc[$i], Notification::getAllActions());
            }
        }
        if (Validation::isWhitespace($HTTP_POST_VARS["note"])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 (
                    not_iss_id,
                    not_usr_id,
                    not_created_date,
                    not_note,
                    not_title";
        if (!@empty($HTTP_POST_VARS['blocked_msg'])) {
            $stmt .= ", not_blocked_message";
        }
        if (!@empty($HTTP_POST_VARS['parent_id'])) {
            $stmt .= ", not_parent_id";
        }
        if ($unknown_user != false) {
            $stmt .= ", not_unknown_user";
        }
        $stmt .= "
                 ) VALUES (
                    $issue_id,
                    $usr_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["note"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["title"]) . "'";
        if (!@empty($HTTP_POST_VARS['blocked_msg'])) {
            $stmt .= ", '" . Misc::escapeString($HTTP_POST_VARS['blocked_msg']) . "'";
        }
        if (!@empty($HTTP_POST_VARS['parent_id'])) {
            $stmt .= ", " . $HTTP_POST_VARS['parent_id'] . "";
        }
        if ($unknown_user != false) {
            $stmt .= ", '" . Misc::escapeString($unknown_user) . "'";
        }
        $stmt .= "
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_note_id = $GLOBALS["db_api"]->get_last_insert_id();
            Issue::markAsUpdated($issue_id, 'note');
            if ($log) {
                // need to save a history entry for this
                History::add($issue_id, $usr_id, History::getTypeID('note_added'), 'Note added by ' . User::getFullName($usr_id));
            }
            // send notifications for the issue being updated
            $internal_only = true;
            if ((@$HTTP_POST_VARS['add_extra_recipients'] != 'yes') && (@count($HTTP_POST_VARS['note_cc']) > 0)) {
                Notification::notify($issue_id, 'notes', $new_note_id, $internal_only, $HTTP_POST_VARS['note_cc']);
            } else {
                Notification::notify($issue_id, 'notes', $new_note_id, $internal_only);
            }
            Workflow::handleNewNote(Issue::getProjectID($issue_id), $issue_id, $usr_id, $closing);
            return 1;
        }
    }


    /**
     * Method used to remove all notes associated with a specific set
     * of issues.
     *
     * @access  public
     * @param   array $ids The list of issues
     * @return  boolean
     */
    function removeByIssues($ids)
    {
        $items = implode(", ", $ids);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_iss_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to remove a specific note from the application.
     *
     * @access  public
     * @param   integer $note_id The note ID
     * @param   boolean $log If this event should be logged or not. Default true
     * @return  integer 1 if the removal worked, -1 or -2 otherwise
     */
    function remove($note_id, $log = true)
    {
        $stmt = "SELECT
                    not_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_id=$note_id";
        $issue_id = $GLOBALS["db_api"]->dbh->getOne($stmt);

        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_id=$note_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($issue_id);
            if ($log) {
                // need to save a history entry for this
                History::add($issue_id, Auth::getUserID(), History::getTypeID('note_removed'), 'Note removed by ' . User::getFullName(Auth::getUserID()));
            }
            return 1;
        }
    }


    /**
     * Method used to get the full listing of notes associated with
     * a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of notes
     */
    function getListing($issue_id)
    {
        $stmt = "SELECT
                    not_id,
                    not_created_date,
                    not_title,
                    not_usr_id,
                    not_unknown_user,
                    IF(LENGTH(not_blocked_message) > 0, 1, 0) AS has_blocked_message,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    not_usr_id=usr_id AND
                    not_iss_id=$issue_id
                 ORDER BY
                    not_created_date ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // only show the internal notes for users with the appropriate permission level
            $role_id = User::getRoleByUser(Auth::getUserID());
            $t = array();
            for ($i = 0; $i < count($res); $i++) {
                if ($role_id < User::getRoleID('standard user')) {
                    continue;
                }
                
                // Display not_unknown_user instead of usr_full_name if not null.
                // This is so the original sender of a blocked email is displayed on the note.
                if (!empty($res[$i]["not_unknown_user"])) {
                    $res[$i]["usr_full_name"] = $res[$i]["not_unknown_user"];
                }
                
                $res[$i]["not_created_date"] = Date_API::getFormattedDate($res[$i]["not_created_date"]);
                $t[] = $res[$i];
            }
            return $t;
        }
    }


    /**
     * Converts a note to a draft or an email
     * 
     * @access  public
     * @param   $note_id The id of the note
     * @param   $target What the not should be converted too
     * @param   $authorize_sender If the sender should be added to authorized senders list.
     */
    function convertNote($note_id, $target, $authorize_sender = false)
    {
        $issue_id = Note::getIssueID($note_id);
        $email_account_id = Email_Account::getEmailAccount();
        $blocked_message = Note::getBlockedMessage($note_id);
        $unknown_user = Note::getUnknownUser($note_id);
        $structure = Mime_Helper::decode($blocked_message, true, true);
        $body = Mime_Helper::getMessageBody($structure);
        $sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));
        $parts = array();
        Mime_Helper::parse_output($structure, $parts);
        if ($target == 'email') {
            if (@count($parts["attachments"]) > 0) {
                $has_attachments = 1;
            } else {
                $has_attachments = 0;
            }
            $t = array(
                'issue_id'       => $issue_id,
                'ema_id'         => $email_account_id,
                'message_id'     => @$structure->headers['message-id'],
                'date'           => Date_API::getCurrentDateGMT(),
                'from'           => @$structure->headers['from'],
                'to'             => @$structure->headers['to'],
                'cc'             => @$structure->headers['cc'],
                'subject'        => @$structure->headers['subject'],
                'body'           => @$body,
                'full_email'     => @$blocked_message,
                'has_attachment' => $has_attachments
            );
            // need to check spot for customer association
            if (!empty($structure->headers['from'])) {
                $details = Email_Account::getDetails($email_account_id);
                // check from the associated project if we need to lookup any customers by this email address
                if (Customer::hasCustomerIntegration($details['ema_prj_id'])) {
                    // check for any customer contact association
                    list($customer_id,) = Customer::getCustomerIDByEmails($details['ema_prj_id'], array($sender_email));
                    if (!empty($customer_id)) {
                        $t['customer_id'] = $customer_id;
                    }
                }
            }
            if (empty($t['customer_id'])) {
                $t['customer_id'] = "NULL";
            }
            $res = Support::insertEmail($t, $structure);
            if ($res != -1) {
                Support::extractAttachments($issue_id, $blocked_message);
                // notifications about new emails are always external
                $internal_only = false;
                // special case when emails are bounced back, so we don't want to notify the customer about those
                if (Notification::isBounceMessage($sender_email)) {
                    $internal_only = true;
                }
                Notification::notifyNewEmail(Auth::getUserID(), $issue_id, $structure, $blocked_message, $internal_only);
                Issue::markAsUpdated($issue_id);
                Note::remove($note_id, false);
                History::add($issue_id, Auth::getUserID(), History::getTypeID('note_converted_email'), 
                        "Note converted to e-mail (from: " . @$structure->headers['from'] . ") by " . User::getFullName(Auth::getUserID()));
                // now add sender as an authorized replier
                if ($authorize_sender) {
                    Authorized_Replier::manualInsert($issue_id, @$structure->headers['from']);
                }
            }
            return $res;
        } else {
            // save message as a draft
            $res = Draft::saveEmail($issue_id, 
                $structure->headers['to'], 
                $structure->headers['cc'],
                $structure->headers['subject'], 
                $body, 
                false, $unknown_user);
            // remove the note, if the draft was created successfully
            if ($res) {
                Note::remove($note_id, false);
                History::add($issue_id, Auth::getUserID(), History::getTypeID('note_converted_draft'), 
                        "Note converted to draft (from: " . @$structure->headers['from'] . ") by " . User::getFullName(Auth::getUserID()));
            }
            return $res;
        }
    }
    
    
    /**
     * Returns the number of notes by a user in a time range.
     * 
     * @access  public
     * @param   string $usr_id The ID of the user
     * @param   integer $start The timestamp of the start date
     * @param   integer $end The timestanp of the end date
     * @return  integer The number of note by the user.
     */
    function getCountByUser($usr_id, $start, $end)
    {
        $stmt = "SELECT
                    COUNT(not_id)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_created_date BETWEEN '$start' AND '$end' AND
                    not_usr_id = $usr_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return $res;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Note Class');
}
?>