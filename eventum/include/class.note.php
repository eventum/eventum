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

class Note
{
    // XXX: put documentation here
    function getSideLinks($issue_id, $not_id)
    {
        $stmt = "SELECT
                    not_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_iss_id=$issue_id";
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


    // XXX: put documentation here
    function getDetails($note_id)
    {
        $stmt = "SELECT
                    *,
                    UNIX_TIMESTAMP(not_created_date) timestamp,
                    not_blocked_message
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                    not_id=$note_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $res['not_created_date'] = Date_API::getFormattedDate($res['not_created_date']);
            $res["not_from"] = User::getFullName($res['not_usr_id']);
            if (!empty($res['not_blocked_message'])) {
                $res['has_blocked_message'] = true;
                $res["attachments"] = Mime_Helper::getAttachmentCIDs($res['not_blocked_message']);
            } else {
                $res['has_blocked_message'] = false;
            }
            return $res;
        }
    }


    // XXX: put documentation here
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


    // XXX: put documentation here
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
        $fp = fopen($path . $filename, 'w');
        fwrite($fp, $message);
        fclose($fp);
        chmod($path . $filename, 0777);
    }


    /**
     * Method used to add a note using the available web services API.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @param   string $note The body of the note
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function addRemote($issue_id, $usr_id, $note)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 (
                    not_iss_id,
                    not_usr_id,
                    not_created_date,
                    not_note
                 ) VALUES (
                    $issue_id,
                    $usr_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . addslashes($note) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_note_id = $GLOBALS["db_api"]->get_last_insert_id();
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this
            History::add($issue_id, 'Remote note added by ' . User::getFullName($usr_id));
            // we need to send the email only to standard users and more in case of internal notes
            $internal_only = true;
            // send notifications for the issue being updated
            Notification::notify($issue_id, 'notes', $new_note_id, $internal_only);
            return 1;
        }
    }


    /**
     * Method used to add a note using the user interface form 
     * available in the application.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        $usr_id = Auth::getUserID();
        if (@$HTTP_POST_VARS['add_extra_recipients'] != 'yes') {
            $note_cc = array();
        } else {
            $note_cc = $HTTP_POST_VARS['note_cc'];
        }
        // add the poster to the list of people to be subscribed to the notification list
        $note_cc[] = $usr_id;
        for ($i = 0; $i < count($note_cc); $i++) {
            Notification::subscribeUser($HTTP_POST_VARS["issue_id"], $note_cc[$i], Notification::getAllActions());
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
        $stmt .= "
                 ) VALUES (
                    " . $HTTP_POST_VARS["issue_id"] . ",
                    $usr_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . $HTTP_POST_VARS["note"] . "',
                    '" . $HTTP_POST_VARS["title"] . "'";
        if (!@empty($HTTP_POST_VARS['blocked_msg'])) {
            $stmt .= ", '" . $HTTP_POST_VARS['blocked_msg'] . "'";
        }
        if (!@empty($HTTP_POST_VARS['parent_id'])) {
            $stmt .= ", " . $HTTP_POST_VARS['parent_id'] . "";
        }
        $stmt .= "
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_note_id = $GLOBALS["db_api"]->get_last_insert_id();
            Issue::markAsUpdated($HTTP_POST_VARS['issue_id']);
            // need to save a history entry for this
            History::add($HTTP_POST_VARS['issue_id'], 'Note added by ' . User::getFullName($usr_id));
            // send notifications for the issue being updated
            $internal_only = true;
            if ((@$HTTP_POST_VARS['add_extra_recipients'] != 'yes') && (@count($HTTP_POST_VARS['note_cc']) > 0)) {
                Notification::notify($HTTP_POST_VARS["issue_id"], 'notes', $new_note_id, $internal_only, $HTTP_POST_VARS['note_cc']);
            } else {
                Notification::notify($HTTP_POST_VARS["issue_id"], 'notes', $new_note_id, $internal_only);
            }
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
     * @return  integer 1 if the removal worked, -1 or -2 otherwise
     */
    function remove($note_id)
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
            // need to save a history entry for this
            History::add($issue_id, 'Note removed by ' . User::getFullName(Auth::getUserID()));
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
            // only show the internal notes for users with permission levels above 'reporter'
            $role_id = User::getRoleByUser(Auth::getUserID());
            $t = array();
            for ($i = 0; $i < count($res); $i++) {
                if ($role_id < User::getRoleID('standard user')) {
                    continue;
                }
                $res[$i]["not_created_date"] = Date_API::getFormattedDate($res[$i]["not_created_date"]);
                $t[] = $res[$i];
            }
            return $t;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Note Class');
}
?>