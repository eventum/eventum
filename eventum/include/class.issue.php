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
// @(#) $Id: s.class.issue.php 1.114 04/01/19 15:15:25-00:00 jpradomaia $
//


/**
 * Class designed to handle all business logic related to the issues in the
 * system, such as adding or updating them or listing them in the grid mode.
 *
 * @author  João Prado Maia <jpm@mysql.com>
 * @version $Revision: 1.114 $
 */

include_once(APP_INC_PATH . "class.validation.php");
include_once(APP_INC_PATH . "class.time_tracking.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.attachment.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.pager.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.category.php");
include_once(APP_INC_PATH . "class.release.php");
include_once(APP_INC_PATH . "class.resolution.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.scm.php");
include_once(APP_INC_PATH . "class.impact_analysis.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "class.phone_support.php");
include_once(APP_INC_PATH . "class.status.php");

$list_headings = array(
    'Priority',
    'Issue ID',
    'Assigned',
    'Time Spent',
    'Category',
    'Status',
    'Created Date',
    'Summary'
);

class Issue
{
    /**
     * Method used to get the full list of issue IDs and their respective 
     * titles associated to a given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of issues
     */
    function getAssocListByProject($prj_id)
    {
        $stmt = "SELECT
                    iss_id,
                    iss_summary
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_prj_id=$prj_id
                 ORDER BY
                    iss_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the status of a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The status ID
     */
    function getStatusID($issue_id)
    {
        $stmt = "SELECT
                    iss_sta_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to set the status of a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $status_id The new status ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function setStatus($issue_id, $status_id)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_sta_id=$status_id,
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "'
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the project associated to a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The project ID
     */
    function getProjectID($issue_id)
    {
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = "SELECT
                    iss_prj_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $returns[$issue_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to remotely set a lock to a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @param   boolean $force_lock Whether we should force the lock or not
     * @return  integer The status ID
     */
    function remoteLock($issue_id, $usr_id, $force_lock)
    {
        if ($force_lock != 'yes') {
            // check if the issue is not already locked by somebody else
            $stmt = "SELECT
                        iss_lock_usr_id
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     WHERE
                        iss_id=$issue_id";
            $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                if (!empty($res)) {
                    if ($res == $usr_id) {
                        return -2;
                    } else {
                        return -3;
                    }
                }
            }
        }

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_lock_usr_id=$usr_id
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // clear up the assignments for this issue, and then assign it to the current user
            Issue::deleteUserAssociations($issue_id);
            Issue::addUserAssociation($issue_id, $usr_id, false);
            // save a history entry about this...
            History::add($issue_id, "Issue remotely locked by " . User::getFullName($usr_id));
            Notification::subscribeUser($issue_id, $usr_id, Notification::getAllActions(), false);
            return 1;
        }
    }


    /**
     * Method used to remotely assign a given issue to an user.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID of the person performing the change
     * @param   boolean $assignee The user ID of the assignee
     * @return  integer The status ID
     */
    function remoteAssign($issue_id, $usr_id, $assignee)
    {
        // clear up the assignments for this issue, and then assign it to the current user
        Issue::deleteUserAssociations($issue_id);
        $res = Issue::addUserAssociation($issue_id, $assignee, false);
        if ($res != -1) {
            // save a history entry about this...
            History::add($issue_id, "Issue remotely assigned to " . User::getFullName($assignee) . " by " . User::getFullName($usr_id));
            Notification::subscribeUser($issue_id, $assignee, Notification::getAllActions(), false);
        }
        return $res;
    }


    /**
     * Method used to remotely set the status of a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID of the person performing this change
     * @param   integer $new_status The new status ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function setRemoteStatus($issue_id, $usr_id, $new_status)
    {
        $sta_id = Status::getStatusID($new_status);

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_sta_id=$sta_id,
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "'
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // record history entry
            $info = User::getNameEmail($usr_id);
            History::add($issue_id, "Status remotely changed to '$new_status' by " . $info['usr_full_name']);
            // send notification email about issue being updated
            Notification::notify($issue_id, 'updated');
            return 1;
        }
    }


    /**
     * Method used to get all issues associated with a status that doesn't have
     * the 'closed' context.
     *
     * @access  public
     * @param   integer $email The email address associated with the user requesting this information
     * @param   boolean $show_all_issues Whether to show all open issues, or just the ones assigned to the given email address
     * @return  array The list of open issues
     */
    function getOpenIssues($email, $show_all_issues)
    {
        $usr_id = User::getUserIDByEmail($email);
        if (empty($usr_id)) {
            return '';
        }
        $projects = Project::getRemoteAssocListByUser($usr_id);
        if (@count($projects) == 0) {
            return '';
        }

        $stmt = "SELECT
                    iss_id,
                    iss_summary,
                    sta_title,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 ON
                    isu_iss_id=iss_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 ON
                    isu_usr_id=usr_id
                 WHERE
                    iss_prj_id IN (" . implode(", ", array_keys($projects)) . ") AND
                    sta_id=iss_sta_id AND
                    sta_is_closed=0";
        if ($show_all_issues == false) {
            $stmt .= " AND
                    usr_email='" . Misc::runSlashes($email) . "'";
        }
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to build the required parameters to simulate an email reply
     * to the user who reported the issue, using the issue details like summary
     * and description as email fields.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The email parameters
     */
    function getReplyDetails($issue_id)
    {
        $stmt = "SELECT
                    UNIX_TIMESTAMP(iss_created_date) AS created_date_ts,
                    usr_full_name AS reporter,
                    usr_email AS reporter_email,
                    iss_description AS description,
                    iss_summary AS sup_subject
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    iss_usr_id=usr_id AND
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the user currently locking the given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The user ID
     */
    function getLockedUserID($issue_id)
    {
        $stmt = "SELECT
                    iss_lock_usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id=$issue_id";
        return $GLOBALS["db_api"]->dbh->getOne($stmt);
    }


    /**
     * Method used to lock a given issue to a specific user.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @return  boolean
     */
    function lock($issue_id, $usr_id)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_lock_usr_id=$usr_id
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            // clear up the assignments for this issue, and then assign it to the current user
            Issue::deleteUserAssociations($issue_id);
            Issue::addUserAssociation($issue_id, $usr_id);
            // save a history entry about this...
            History::add($issue_id, "Issue locked by " . User::getFullName($usr_id));
            Notification::subscribeUser($issue_id, $usr_id, Notification::getAllActions());
            return true;
        }
    }


    /**
     * Method used to unlock a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  boolean
     */
    function unlock($issue_id)
    {
        $usr_id = Auth::getUserID();

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_lock_usr_id=NULL
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            // save a history entry about this...
            History::add($issue_id, "Issue unlocked by " . User::getFullName($usr_id));
            return true;
        }
    }


    /**
     * Method used to record the last updated timestamp for a given
     * issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  boolean
     */
    function markAsUpdated($issue_id)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "'
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to check whether a given issue has duplicates 
     * or not.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  boolean
     */
    function hasDuplicates($issue_id)
    {
        $stmt = "SELECT
                    COUNT(iss_id)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_duplicated_iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($res == 0) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Method used to update the duplicated issues for a given 
     * issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function updateDuplicates($issue_id)
    {
        global $HTTP_POST_VARS;

        $ids = Issue::getDuplicateList($issue_id);
        if ($ids == '') {
            return -1;
        }
        $ids = @array_keys($ids);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_prc_id=" . $HTTP_POST_VARS["category"] . ",";
        if (@$HTTP_POST_VARS["keep"] == "no") {
            $stmt .= "iss_pre_id=" . $HTTP_POST_VARS["release"] . ",";
        }
        $stmt .= "
                    iss_pri_id=" . $HTTP_POST_VARS["priority"] . ",
                    iss_sta_id=" . $HTTP_POST_VARS["status"] . ",
                    iss_res_id=" . $HTTP_POST_VARS["resolution"] . "
                 WHERE
                    iss_id IN (" . implode(", ", $ids) . ")";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // record the change
            for ($i = 0; $i < count($ids); $i++) {
                History::add($ids[$i], "The details for issue #$issue_id were updated by " . User::getFullName(Auth::getUserID()) . " and the changes propagated to the duplicated issues.");
            }
            return 1;
        }
    }


    /**
     * Method used to get a list of the duplicate issues for a given 
     * issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of duplicates
     */
    function getDuplicateList($issue_id)
    {
        $stmt = "SELECT
                    iss_id,
                    iss_summary
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_duplicated_iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            if (@count($res) == 0) {
                return '';
            } else {
                return $res;
            }
        }
    }


    /**
     * Method used to clear the duplicate status of an issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function clearDuplicateStatus($issue_id)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_duplicated_iss_id=NULL
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // record the change
            History::add($issue_id, "Duplicate flag was reset by " . User::getFullName(Auth::getUserID()));
            return 1;
        }
    }


    /**
     * Method used to mark an issue as a duplicate of an existing one.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function markAsDuplicate($issue_id)
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_duplicated_iss_id=" . $HTTP_POST_VARS["duplicated_issue"] . "
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if (!empty($HTTP_POST_VARS["comments"])) {
                // add note with the comments of marking an issue as a duplicate of another one
                $HTTP_POST_VARS["note"] = Misc::runSlashes($HTTP_POST_VARS["comments"]);
                Note::insert();
            }
            // record the change
            History::add($issue_id, "Issue marked as a duplicate of issue #" . $HTTP_POST_VARS["duplicated_issue"] . " by " . User::getFullName(Auth::getUserID()));
            return 1;
        }
    }


    /**
     * Method used to get an associative array of user ID => user 
     * status associated with a given issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of users
     */
    function getAssignedUsersStatus($issue_id)
    {
        $stmt = "SELECT
                    usr_id,
                    usr_status
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    isu_iss_id=$issue_id AND
                    isu_usr_id=usr_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of issues as an associative array
     * associated with a given release.
     *
     * @access  public
     * @param   integer $pre_id The release ID
     * @return  array The list of issues
     */
    function getAssocListByRelease($pre_id)
    {
        $stmt = "SELECT
                    iss_id,
                    iss_summary
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_prj_id=" . Auth::getCurrentProject() . " AND
                    iss_pre_id=$pre_id
                 ORDER BY
                    iss_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the summary associated with a given issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  string The issue summary
     */
    function getTitle($issue_id)
    {
        $stmt = "SELECT
                    iss_summary
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the issue ID associated with a specific summary.
     *
     * @access  public
     * @param   string $summary The summary to look for
     * @return  integer The issue ID
     */
    function getIssueID($summary)
    {
        $stmt = "SELECT
                    iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_summary='" . addslashes($summary) . "'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } else {
            if (empty($res)) {
                return 0;
            } else {
                return $res;
            }
        }
    }


    /**
     * Method used to add a new anonymous based issue in the system.
     *
     * @access  public
     * @return  integer The new issue ID
     */
    function addAnonymousReport()
    {
        global $HTTP_POST_VARS, $HTTP_POST_FILES;

        $options = Project::getAnonymousPostOptions($HTTP_POST_VARS["project"]);
        $initial_status = Project::getInitialStatus($HTTP_POST_VARS["project"]);
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 (
                    iss_prj_id,
                    iss_prc_id,
                    iss_pre_id,
                    iss_pri_id,
                    iss_usr_id,";
        if (!empty($initial_status)) {
            $stmt .= "iss_sta_id,";
        }
        $stmt .= "
                    iss_created_date,
                    iss_summary,
                    iss_description
                 ) VALUES (
                    " . $HTTP_POST_VARS["project"] . ",
                    " . $options["category"] . ",
                    0,
                    " . $options["priority"] . ",
                    " . $options["reporter"] . ",";
        if (!empty($initial_status)) {
            $stmt .= "$initial_status,";
        }
        $stmt .= "
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["summary"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["description"]) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return $res;
        } else {
            $new_issue_id = $GLOBALS["db_api"]->get_last_insert_id();
            // log the creation of the issue
            History::add($new_issue_id, 'Issue opened anonymously');
            // now add the user/issue association
            $assign = array();
            $users = $options["users"];
            for ($i = 0; $i < count($users); $i++) {
                Notification::insert($new_issue_id, $users[$i]);
                Issue::addUserAssociation($new_issue_id, $users[$i]);
                $assign[] = $users[$i];
            }
            if (count($assign)) {
                Notification::notifyAssignedUsers($assign, $new_issue_id);
            }
            // also notify any users that want to receive emails anytime a new issue is created
            Notification::notifyNewIssue($HTTP_POST_VARS['project'], $new_issue_id, $assign);
            // now process any files being uploaded
            $found = 0;
            for ($i = 0; $i < count(@$HTTP_POST_FILES["file"]["name"]); $i++) {
                if (!@empty($HTTP_POST_FILES["file"]["name"][$i])) {
                    $found = 1;
                    break;
                }
            }
            if ($found) {
                $attachment_id = Attachment::add($new_issue_id, $options["reporter"], 'files uploaded anonymously');
                for ($i = 0; $i < count(@$HTTP_POST_FILES["file"]["name"]); $i++) {
                    $filename = @$HTTP_POST_FILES["file"]["name"][$i];
                    if (empty($filename)) {
                        continue;
                    }
                    $blob = Misc::getFileContents($HTTP_POST_FILES["file"]["tmp_name"][$i]);
                    if (!empty($blob)) {
                        Attachment::addFile($attachment_id, $new_issue_id, $filename, $HTTP_POST_FILES["file"]["type"][$i], $blob);
                    }
                }
            }
            // need to process any custom fields ?
            if (@count($HTTP_POST_VARS["custom_fields"]) > 0) {
                foreach ($HTTP_POST_VARS["custom_fields"] as $fld_id => $value) {
                    Custom_Field::associateIssue($new_issue_id, $fld_id, $value);
                }
            }
            return $new_issue_id;
        }
    }


    /**
     * Method used to add a new issue using the web services API available in
     * the application.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $category The category ID
     * @param   integer $priority The priority ID
     * @param   integer $reporter The reporter' user ID
     * @param   array $users The list of users to assign this new issue to
     * @param   string $summary The title of the new issue
     * @param   string $description The description of the new issue
     * @return  integer The new issue ID
     */
    function addRemote($prj_id, $category, $priority, $reporter, $users, $summary, $description)
    {
        $initial_status = Project::getInitialStatus($prj_id);
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 (
                    iss_prj_id,
                    iss_prc_id,
                    iss_pre_id,
                    iss_pri_id,
                    iss_usr_id,";
        if (!empty($initial_status)) {
            $stmt .= "iss_sta_id,";
        }
        $stmt .= "
                    iss_created_date,
                    iss_summary,
                    iss_description
                 ) VALUES (
                    $prj_id,
                    $category,
                    0,
                    $priority,
                    $reporter,";
        if (!empty($initial_status)) {
            $stmt .= "$initial_status,";
        }
        $stmt .= "
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::runSlashes($summary) . "',
                    '" . Misc::runSlashes($description) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return $res;
        } else {
            $new_issue_id = $GLOBALS["db_api"]->get_last_insert_id();
            // log the creation of the issue
            History::add($new_issue_id, 'Issue opened remotely');
            // now add the user/issue association
            $assign = array();
            for ($i = 0; $i < count($users); $i++) {
                Notification::insert($new_issue_id, $users[$i]);
                Issue::addUserAssociation($new_issue_id, $users[$i]);
                $assign[] = $users[$i];
            }
            if (count($assign)) {
                Notification::notifyAssignedUsers($assign, $new_issue_id);
            }
            // also notify any users that want to receive emails anytime a new issue is created
            Notification::notifyNewIssue($prj_id, $new_issue_id, $assign);
            return $new_issue_id;
        }
    }


    /**
     * Method used to remove all issues associated with a specific list of
     * projects.
     *
     * @access  public
     * @param   array $ids The list of projects to look for
     * @return  boolean
     */
    function removeByProjects($ids)
    {
        $items = @implode(", ", $ids);
        $stmt = "SELECT
                    iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_prj_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Issue::deleteAssociations($res);
            Attachment::removeByIssues($res);
            SCM::removeByIssues($res);
            Impact_Analysis::removeByIssues($res);
            Issue::deleteUserAssociations($res);
            Note::removeByIssues($res);
            Time_Tracking::removeByIssues($res);
            Notification::removeByIssues($res);
            Custom_Field::removeByIssues($res);
            Phone_Support::removeByIssues($res);
            History::removeByIssues($res);
            // now really delete the issues
            $items = implode(", ", $res);
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     WHERE
                        iss_id IN ($items)";
            $GLOBALS["db_api"]->dbh->query($stmt);
            return true;
        }
    }


    /**
     * Method used to close off an issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function close($issue_id)
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_closed_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_sta_id=" . $HTTP_POST_VARS['status'] . "
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // unlock the issue, if needed
            Issue::unlock($issue_id);
            // add note with the reason to close the issue
            $HTTP_POST_VARS["note"] = Misc::runSlashes($HTTP_POST_VARS["reason"]);
            Note::insert();
            // record the change
            History::add($issue_id, "Issue updated to status '" . Status::getStatusTitle($HTTP_POST_VARS['status']) . "' by " . User::getFullName(Auth::getUserID()));
            // send notifications for the issue being closed
            Notification::notify($issue_id, 'closed');
            return 1;
        }
    }


    /**
     * Method used to update the details of a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    function update($issue_id)
    {
        global $HTTP_POST_VARS;

        $usr_id = Auth::getUserID();
        // get all of the 'current' information of this issue
        $current = Issue::getDetails($issue_id);
        // update the issue associations
        Issue::deleteAssociations($issue_id);
        for ($i = 0; $i < count(@$HTTP_POST_VARS["associated_issues"]); $i++) {
            Issue::addAssociation($issue_id, $HTTP_POST_VARS["associated_issues"][$i]);
        }
        if (@$HTTP_POST_VARS["keep_assignments"] == "no") {
            // update the issue-user associations
            Issue::deleteUserAssociations($issue_id);
            for ($i = 0; $i < count(@$HTTP_POST_VARS["assignments"]); $i++) {
                Issue::addUserAssociation($issue_id, $HTTP_POST_VARS["assignments"][$i]);
                Notification::subscribeUser($issue_id, $HTTP_POST_VARS["assignments"][$i], Notification::getAllActions());
            }
        }
        if (empty($HTTP_POST_VARS["estimated_dev_time"])) {
            $HTTP_POST_VARS["estimated_dev_time"] = 0;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_prc_id=" . $HTTP_POST_VARS["category"] . ",";
        if (@$HTTP_POST_VARS["keep"] == "no") {
            $stmt .= "iss_pre_id=" . $HTTP_POST_VARS["release"] . ",";
        }
        $stmt .= "
                    iss_pri_id=" . $HTTP_POST_VARS["priority"] . ",
                    iss_sta_id=" . $HTTP_POST_VARS["status"] . ",
                    iss_res_id=" . $HTTP_POST_VARS["resolution"] . ",
                    iss_summary='" . Misc::runSlashes($HTTP_POST_VARS["summary"]) . "',
                    iss_description='" . Misc::runSlashes($HTTP_POST_VARS["description"]) . "',
                    iss_dev_time=" . $HTTP_POST_VARS["estimated_dev_time"] . "
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // add change to the history (only for changes on specific fields?)
            $updated_fields = array();
            if ($current["iss_prc_id"] != $HTTP_POST_VARS["category"]) {
                $updated_fields["Category"] = History::formatChanges(Category::getTitle($current["iss_prc_id"]), Category::getTitle($HTTP_POST_VARS["category"]));
            }
            if ((@$HTTP_POST_VARS["keep"] == "no") && ($current["iss_pre_id"] != $HTTP_POST_VARS["release"])) {
                $updated_fields["Release"] = History::formatChanges(Release::getTitle($current["iss_pre_id"]), Release::getTitle($HTTP_POST_VARS["release"]));
            }
            if ($current["iss_pri_id"] != $HTTP_POST_VARS["priority"]) {
                $updated_fields["Priority"] = History::formatChanges(Misc::getPriorityTitle($current["iss_pri_id"]), Misc::getPriorityTitle($HTTP_POST_VARS["priority"]));
            }
            if ($current["iss_sta_id"] != $HTTP_POST_VARS["status"]) {
                $updated_fields["Status"] = History::formatChanges(Status::getStatusTitle($current["iss_sta_id"]), Status::getStatusTitle($HTTP_POST_VARS["status"]));
            }
            if ($current["iss_res_id"] != $HTTP_POST_VARS["resolution"]) {
                $updated_fields["Resolution"] = History::formatChanges(Resolution::getTitle($current["iss_res_id"]), Resolution::getTitle($HTTP_POST_VARS["resolution"]));
            }
            if ($current["iss_dev_time"] != $HTTP_POST_VARS["estimated_dev_time"]) {
                $updated_fields["Estimated Dev. Time"] = History::formatChanges(Misc::getFormattedTime($current["iss_dev_time"]), Misc::getFormattedTime($HTTP_POST_VARS["estimated_dev_time"]));
            }
            if ($current["iss_summary"] != $HTTP_POST_VARS["summary"]) {
                $updated_fields["Summary"] = '';
            }
            if ($current["iss_description"] != $HTTP_POST_VARS["description"]) {
                $updated_fields["Description"] = '';
            }
            if (count($updated_fields) > 0) {
                // log the changes
                $changes = '';
                $i = 0;
                foreach ($updated_fields as $key => $value) {
                    if ($i > 0) {
                        $changes .= "; ";
                    }
                    if (($key != "Summary") && ($key != "Description")) {
                        $changes .= "$key: $value";
                    } else {
                        $changes .= "$key";
                    }
                    $i++;
                }
                History::add($issue_id, "Issue updated ($changes) by " . User::getFullName($usr_id));
            }
            // send notifications for the issue being updated
            Notification::notify($issue_id, 'updated');
            // now update any duplicates, if any
            $update_dupe = array(
                'Category',
                'Release',
                'Priority',
                'Release',
                'Resolution'
            );
            // COMPAT: the following line requires PHP > 4.0.4
            $intersect = array_intersect($update_dupe, array_keys($updated_fields));
            if (($current["duplicates"] != '') && (count($intersect) > 0)) {
                Issue::updateDuplicates($issue_id);
            }
            return 1;
        }
    }


    /**
     * Method used to associate an existing issue with another one.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $issue_id The other issue ID
     * @return  void
     */
    function addAssociation($issue_id, $associated_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_association
                 (
                    isa_issue_id,
                    isa_associated_id
                 ) VALUES (
                    $issue_id,
                    $associated_id
                 )";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to remove the issue associations related to a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  void
     */
    function deleteAssociations($issue_id)
    {
        if (is_array($issue_id)) {
            $issue_id = implode(", ", $issue_id);
        }
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_association
                 WHERE
                    isa_issue_id IN ($issue_id)";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to assign an issue with an user.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function addUserAssociation($issue_id, $usr_id, $add_history = TRUE)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 (
                    isu_iss_id,
                    isu_usr_id
                 ) VALUES (
                    $issue_id,
                    $usr_id
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if ($add_history) {
                History::add($issue_id, 'Issue assigned to ' . User::getFullName($usr_id) . ' by ' . User::getFullName(Auth::getUserID()));
            }
            return 1;
        }
    }


    /**
     * Method used to delete all user assignments for a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  void
     */
    function deleteUserAssociations($issue_id)
    {
        if (is_array($issue_id)) {
            $issue_id = implode(", ", $issue_id);
        }
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 WHERE
                    isu_iss_id IN ($issue_id)";
        $GLOBALS["db_api"]->dbh->query($stmt);
        History::add($issue_id, 'Issue assignments removed');
    }


    /**
     * Method used to add a new issue using the normal report form.
     *
     * @access  public
     * @return  integer The new issue ID
     */
    function insert()
    {
        global $HTTP_POST_VARS, $HTTP_POST_FILES;

        $missing_fields = array();
        if ($HTTP_POST_VARS["category"] == '-1') {
            $missing_fields[] = "Category";
        }
        if ($HTTP_POST_VARS["priority"] == '-1') {
            $missing_fields[] = "Priority";
        }

        if ($HTTP_POST_VARS["estimated_dev_time"] == '') {
            $HTTP_POST_VARS["estimated_dev_time"] = 0;
        }
        $usr_id = Auth::getUserID();
        $prj_id = Auth::getCurrentProject();
        $initial_status = Project::getInitialStatus($prj_id);
        // add new issue
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 (
                    iss_prj_id,
                    iss_prc_id,
                    iss_pre_id,
                    iss_pri_id,
                    iss_usr_id,";
        if (!empty($initial_status)) {
            $stmt .= "iss_sta_id,";
        }
        $stmt .= "
                    iss_created_date,
                    iss_summary,
                    iss_description,
                    iss_dev_time
                 ) VALUES (
                    " . $prj_id . ",
                    " . $HTTP_POST_VARS["category"] . ",
                    " . $HTTP_POST_VARS["release"] . ",
                    " . $HTTP_POST_VARS["priority"] . ",
                    " . $usr_id . ",";
        if (!empty($initial_status)) {
            $stmt .= "$initial_status,";
        }
        $stmt .= "
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["summary"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["description"]) . "',
                    " . $HTTP_POST_VARS["estimated_dev_time"] . "
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_issue_id = $GLOBALS["db_api"]->get_last_insert_id();
            // log the creation of the issue
            History::add($new_issue_id, 'Issue opened by ' . User::getFullName(Auth::getUserID()));
            // now add the user/issue association
            $users = array();
            for ($i = 0; $i < count($HTTP_POST_VARS["users"]); $i++) {
                Notification::insert($new_issue_id, $HTTP_POST_VARS["users"][$i]);
                Issue::addUserAssociation($new_issue_id, $HTTP_POST_VARS["users"][$i]);
                if ($HTTP_POST_VARS["users"][$i] != $usr_id) {
                    $users[] = $HTTP_POST_VARS["users"][$i];
                }
            }
            if (count($users)) {
                Notification::notifyAssignedUsers($users, $new_issue_id);
            }
            // also notify any users that want to receive emails anytime a new issue is created
            Notification::notifyNewIssue($prj_id, $new_issue_id, $users);
            // now process any files being uploaded
            $found = 0;
            for ($i = 0; $i < count(@$HTTP_POST_FILES["file"]["name"]); $i++) {
                if (!@empty($HTTP_POST_FILES["file"]["name"][$i])) {
                    $found = 1;
                    break;
                }
            }
            if ($found) {
                $attachment_id = Attachment::add($new_issue_id, $usr_id, '');
                for ($i = 0; $i < count(@$HTTP_POST_FILES["file"]["name"]); $i++) {
                    $filename = @$HTTP_POST_FILES["file"]["name"][$i];
                    if (empty($filename)) {
                        continue;
                    }
                    $blob = Misc::getFileContents($HTTP_POST_FILES["file"]["tmp_name"][$i]);
                    if (!empty($blob)) {
                        Attachment::addFile($attachment_id, $new_issue_id, $filename, $HTTP_POST_FILES["file"]["type"][$i], $blob);
                    }
                }
            }
            // need to associate any emails ?
            if (!empty($HTTP_POST_VARS["attached_emails"])) {
                $HTTP_POST_VARS["item"] = explode(",", $HTTP_POST_VARS["attached_emails"]);
                $HTTP_POST_VARS["issue"] = $new_issue_id;
                Support::associate();
            }
            // need to process any custom fields ?
            if (@count($HTTP_POST_VARS["custom_fields"]) > 0) {
                foreach ($HTTP_POST_VARS["custom_fields"] as $fld_id => $value) {
                    Custom_Field::associateIssue($new_issue_id, $fld_id, $value);
                }
            }
            // now subscribe the reporter of this issue (if needed)
            if (@$HTTP_POST_VARS["receive_notifications"] == 1) {
                // get the actual preference for this subscription
                if ($HTTP_POST_VARS["choice"] == 'default') {
                    Notification::insert($new_issue_id, $usr_id);
                } else {
                    Notification::subscribeReporter($new_issue_id, $usr_id, $HTTP_POST_VARS["actions"]);
                }
            }
            return $new_issue_id;
        }
    }


    /**
     * Method used to get the current listing related cookie information.
     *
     * @access  public
     * @return  array The issue listing information
     */
    function getCookieParams()
    {
        global $HTTP_COOKIE_VARS;
        return @unserialize(base64_decode($HTTP_COOKIE_VARS[APP_LIST_COOKIE]));
    }


    /**
     * Method used to get a specific parameter in the issue listing cookie.
     *
     * @access  public
     * @param   string $name The name of the parameter
     * @return  mixed The value of the specified parameter
     */
    function getParam($name)
    {
        global $HTTP_POST_VARS, $HTTP_GET_VARS;
        $cookie = Issue::getCookieParams();

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
        $sort_by = Issue::getParam('sort_by');
        $sort_order = Issue::getParam('sort_order');
        $rows = Issue::getParam('rows');
        $cookie = array(
            'rows'           => $rows ? $rows : APP_DEFAULT_PAGER_SIZE,
            'pagerRow'       => Issue::getParam('pagerRow'),
            'hide_closed'    => Issue::getParam('hide_closed'),
            "sort_by"        => $sort_by ? $sort_by : "iss_pri_id",
            "sort_order"     => $sort_order ? $sort_order : "ASC",
            // quick filter form
            'keywords'       => Issue::getParam('keywords'),
            'users'          => Issue::getParam('users'),
            'status'         => Issue::getParam('status'),
            'priority'       => Issue::getParam('priority'),
            'category'       => Issue::getParam('category')
        );
        // now do some magic to properly format the date fields
        $date_fields = array(
            'created_date',
            'updated_date',
            'last_response_date',
            'first_response_date',
            'closed_date'
        );
        foreach ($date_fields as $field_name) {
            $field = Issue::getParam($field_name);
            if (empty($field)) {
                continue;
            }
            $end_field_name = $field_name . '_end';
            $end_field = Issue::getParam($end_field_name);
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
        setcookie(APP_LIST_COOKIE, $encoded, APP_LIST_COOKIE_EXPIRE);
        return $cookie;
    }


    /**
     * Method used to get the current sorting options used in the grid layout
     * of the issue listing page.
     *
     * @access  public
     * @param   array $options The current search parameters
     * @return  array The sorting options
     */
    function getSortingInfo($options)
    {
        global $HTTP_SERVER_VARS;

        $fields = array(
            "iss_pri_id",
            "iss_id",
            "iss_prc_id",
            "iss_sta_id",
            "iss_created_date",
            "iss_summary"
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
     * Method used to get the list of issues to be displayed in the grid layout.
     *
     * @access  public
     * @param   integer $prj_id The current project ID
     * @param   array $options The search parameters
     * @param   integer $current_row The current page number
     * @param   integer $max The maximum number of rows per page
     * @return  array The list of issues to be displayed
     */
    function getListing($prj_id, $options, $current_row = 0, $max = 5, $get_reporter = FALSE)
    {
        global $list_headings;

        if ($max == "ALL") {
            $max = 9999999;
        }
        $start = $current_row * $max;
        // get the current user's role
        $usr_id = Auth::getUserID();
        $role_id = User::getRoleByUser($usr_id);

        $stmt = "SELECT
                    iss_id,
                    iss_lock_usr_id,
                    iss_created_date,
                    iss_usr_id,
                    iss_summary,
                    pri_title,
                    prc_title,
                    sta_title,
                    sta_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue";
        if (!empty($options["users"])) {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 ON
                    isu_iss_id=iss_id";
        }
        $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category
                 ON
                    iss_prc_id=prc_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "priority
                 ON
                    iss_pri_id=pri_id
                 WHERE
                    iss_prj_id=$prj_id";
        $stmt .= Issue::buildWhereClause($options);
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
            if (count($res) > 0) {
                if ($get_reporter) {
                    Issue::getReportersByIssues($res);
                }
                Issue::getAssignedUsersByIssues($res);
                Time_Tracking::getTimeSpentByIssues($res);
            }
            $csv[] = @implode("\t", $list_headings);
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["iss_created_date"] = Date_API::getFormattedDate($res[$i]["iss_created_date"]);
                $res[$i]["status_color"] = Status::getStatusColor($res[$i]["sta_id"]);
                $res[$i]["time_spent"] = Misc::getFormattedTime($res[$i]["time_spent"]);
                $fields = array(
                    $res[$i]['pri_title'],
                    $res[$i]['iss_id'],
                    $res[$i]['assigned_users'],
                    $res[$i]['time_spent'],
                    $res[$i]['prc_title'],
                    $res[$i]['sta_title'],
                    $res[$i]["iss_created_date"],
                    $res[$i]['iss_summary']
                );
                $csv[] = @implode("\t", $fields);
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
                ),
                "csv" => @implode("\n", $csv)
            );
        }
    }


    /**
     * Method used to get the list of issues to be displayed in the grid layout.
     *
     * @access  public
     * @param   array $options The search parameters
     * @return  string The where clause
     */
    function buildWhereClause($options)
    {
        $usr_id = Auth::getUserID();

        $stmt = '';
        if (!empty($options["users"])) {
            $stmt .= " AND (
                    isu_usr_id";
            if ($options['users'] == '-1') {
                $stmt .= ' IS NULL';
            } elseif ($options['users'] == '-2') {
                $stmt .= ' IS NULL OR isu_usr_id=' . Auth::getUserID();
            } else {
                $stmt .= '=' . $options["users"];
            }
            $stmt .= ')';
        }
        if (!empty($options["keywords"])) {
            $stmt .= " AND (" . Misc::prepareBooleanSearch('iss_summary', Misc::runSlashes($options["keywords"]));
            $stmt .= " OR " . Misc::prepareBooleanSearch('iss_description', Misc::runSlashes($options["keywords"])) . ")";
        }
        if (!empty($options["priority"])) {
            $stmt .= " AND iss_pri_id=" . $options["priority"];
        }
        if (!empty($options["status"])) {
            $stmt .= " AND iss_sta_id=" . $options["status"];
        }
        if (!empty($options["category"])) {
            $stmt .= " AND iss_prc_id=" . $options["category"];
        }
        if (!empty($options["hide_closed"])) {
            $stmt .= " AND sta_is_closed=0";
        }
        // now for the date fields
        $date_fields = array(
            'created_date',
            'updated_date',
            'last_response_date',
            'first_response_date',
            'closed_date'
        );
        foreach ($date_fields as $field_name) {
            if (!empty($options[$field_name])) {
                switch ($options[$field_name]['filter_type']) {
                    case 'greater':
                        $stmt .= " AND iss_$field_name >= '" . $options[$field_name]['start'] . "'";
                        break;
                    case 'less':
                        $stmt .= " AND iss_$field_name <= '" . $options[$field_name]['start'] . "'";
                        break;
                    case 'between':
                        $stmt .= " AND iss_$field_name BETWEEN '" . $options[$field_name]['start'] . "' AND '" . $options[$field_name]['end'] . "'";
                        break;
                }
            }
        }

        return $stmt;
    }


    /**
     * Method used to get the previous and next issues that are available 
     * according to the current search parameters.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   array $options The search parameters
     * @return  array The list of issues
     */
    function getSides($issue_id, $options)
    {
        $usr_id = Auth::getUserID();
        $role_id = User::getRoleByUser($usr_id);

        $stmt = "SELECT
                    iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue";
        if (!empty($options["users"])) {
            $stmt .= ",
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user";
        }
        $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "priority
                 ON
                    iss_pri_id=pri_id
                 WHERE
                    iss_prj_id=" . Auth::getCurrentProject();
        $stmt .= Issue::buildWhereClause($options);
        $stmt .= "
                 ORDER BY
                    " . $options["sort_by"] . " " . $options["sort_order"];
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // COMPAT: the next line requires PHP >= 4.0.5
            $index = array_search($issue_id, $res);
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
     * Method used to get the full list of user IDs assigned to a specific 
     * issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of user IDs
     */
    function getAssignedUserIDs($issue_id)
    {
        $stmt = "SELECT
                    usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    isu_iss_id=$issue_id AND
                    isu_usr_id=usr_id";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full list of reporters associated with a given 
     * list of issues.
     *
     * @access  public
     * @param   array $result The result set
     * @return  void
     */
    function getReportersByIssues(&$result)
    {
        $ids = array();
        for ($i = 0; $i < count($result); $i++) {
            $ids[] = $result[$i]["iss_id"];
        }
        $ids = implode(", ", $ids);
        $stmt = "SELECT
                    iss_id,
                    CONCAT(usr_full_name, ' <', usr_email, '>') AS usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    iss_usr_id=usr_id AND
                    iss_id IN ($ids)";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            // now populate the $result variable again
            for ($i = 0; $i < count($result); $i++) {
                @$result[$i]['reporter'] = $res[$result[$i]['iss_id']];
            }
        }
    }


    /**
     * Method used to get the full list of assigned users by a list
     * of issues. This was originally created to optimize the issue
     * listing page.
     *
     * @access  public
     * @param   array $result The result set
     * @return  void
     */
    function getAssignedUsersByIssues(&$result)
    {
        $ids = array();
        for ($i = 0; $i < count($result); $i++) {
            $ids[] = $result[$i]["iss_id"];
        }
        $ids = implode(", ", $ids);
        $stmt = "SELECT
                    isu_iss_id,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    isu_usr_id=usr_id AND
                    isu_iss_id IN ($ids)";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            $t = array();
            for ($i = 0; $i < count($res); $i++) {
                if (!empty($t[$res[$i]['isu_iss_id']])) {
                    $t[$res[$i]['isu_iss_id']] .= ', ' . $res[$i]['usr_full_name'];
                } else {
                    $t[$res[$i]['isu_iss_id']] = $res[$i]['usr_full_name'];
                }
            }
            // now populate the $result variable again
            for ($i = 0; $i < count($result); $i++) {
                @$result[$i]['assigned_users'] = $t[$result[$i]['iss_id']];
            }
        }
    }


    /**
     * Method used to get the full list of users (the full names) assigned to a
     * specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of users
     */
    function getAssignedUsers($issue_id)
    {
        $stmt = "SELECT
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    isu_iss_id=$issue_id AND
                    isu_usr_id=usr_id";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the details for a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The details for the specified issue
     */
    function getDetails($issue_id)
    {
        global $HTTP_SERVER_VARS;
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue.*,
                    prc_title,
                    pre_title,
                    pri_title,
                    sta_title,
                    sta_is_closed
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "priority
                 ON
                    iss_pri_id=pri_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category
                 ON
                    iss_prc_id=prc_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 ON
                    iss_pre_id=pre_id
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (empty($res)) {
                return "";
            } else {
                $res['iss_original_description'] = $res["iss_description"];
                if (!strstr($HTTP_SERVER_VARS["PHP_SELF"], 'update.php')) {
                    $res["iss_description"] = Misc::activateLinks(nl2br(htmlspecialchars($res["iss_description"])));
                    $res["iss_description"] = Misc::activateIssueLinks($res["iss_description"]);
                    $res["iss_resolution"] = Resolution::getTitle($res["iss_res_id"]);
                }
                $res["iss_impact_analysis"] = Misc::activateIssueLinks(nl2br(htmlspecialchars($res["iss_impact_analysis"])));
                $res["iss_created_date"] = Date_API::getFormattedDate($res["iss_created_date"]);
                $res["assignments"] = @implode(", ", array_values(Issue::getAssignedUsers($res["iss_id"])));
                $temp = Issue::getAssignedUsersStatus($res["iss_id"]);
                $res["has_inactive_users"] = 0;
                $res["assigned_users"] = array();
                foreach ($temp as $usr_id => $usr_status) {
                    if (!User::isActiveStatus($usr_status)) {
                        $res["has_inactive_users"] = 1;
                    } else {
                        $res["assigned_users"][] = $usr_id;
                    }
                }
                if (@in_array(Auth::getUserID(), $res["assigned_users"])) {
                    $res["is_current_user_assigned"] = 1;
                } else {
                    $res["is_current_user_assigned"] = 0;
                }
                $res["associated_issues"] = Issue::getAssociatedIssues($res["iss_id"]);
                $res["reporter"] = User::getFullName($res["iss_usr_id"]);
                if (empty($res["iss_updated_date"])) {
                    $res["iss_updated_date"] = 'not updated yet';
                } else {
                    $res["iss_updated_date"] = Date_API::getFormattedDate($res["iss_updated_date"]);
                }
                $res["status_color"] = Status::getStatusColor($res["iss_sta_id"]);
                $res["estimated_formatted_time"] = Misc::getFormattedTime($res["iss_dev_time"]);
                if (Release::isAssignable($res["iss_pre_id"])) {
                    $release = Release::getDetails($res["iss_pre_id"]);
                    $res["pre_title"] = $release["pre_title"];
                    $res["pre_status"] = $release["pre_status"];
                }
                // need to return the list of issues that are duplicates of this one
                $res["duplicates"] = Issue::getDuplicateList($res["iss_id"]);
                // also get the issue title of the duplicated issue
                if (!empty($res['iss_duplicated_iss_id'])) {
                    $res['iss_duplicated_iss_title'] = Issue::getTitle($res['iss_duplicated_iss_id']);
                }

                $returns[$issue_id] = $res;
                return $res;
            }
        }
    }


    /**
     * Method used to assign a list of issues to a specific user.
     *
     * @access  public
     * @return  boolean
     */
    function assign()
    {
        global $HTTP_POST_VARS;

        for ($i = 0; $i < count($HTTP_POST_VARS["item"]); $i++) {
            // check if the bug is already assigned to this person
            $stmt = "SELECT
                        COUNT(*) AS total
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                     WHERE
                        isu_iss_id=" . $HTTP_POST_VARS["item"][$i] . " AND
                        isu_usr_id=" . $HTTP_POST_VARS["users"];
            $total = $GLOBALS["db_api"]->dbh->getOne($stmt);
            if ($total > 0) {
                continue;
            } else {
                // add the assignment
                $stmt = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                         (
                            isu_iss_id,
                            isu_usr_id
                         ) VALUES (
                            " . $HTTP_POST_VARS["item"][$i] . ",
                            " . $HTTP_POST_VARS["users"] . "
                         )";
                $GLOBALS["db_api"]->dbh->query($stmt);
                // add the assignment to the history of the issue
                $summary = 'Issue assigned to ' . User::getFullName($HTTP_POST_VARS["users"]) . ' by ' . User::getFullName(Auth::getUserID());
                History::add($HTTP_POST_VARS["item"][$i], $summary);
            }
        }
        return true;
    }


    /**
     * Method used to set the initial impact analysis for a specific issue
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function setImpactAnalysis($issue_id)
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_developer_est_time=" . $HTTP_POST_VARS["dev_time"] . ",
                    iss_impact_analysis='" . Misc::runSlashes($HTTP_POST_VARS["impact_analysis"]) . "'
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // add the impact analysis to the history of the issue
            $summary = 'Initial Impact Analysis for issue set by ' . User::getFullName(Auth::getUserID());
            History::add($issue_id, $summary);
            return 1;
        }
    }


    /**
     * Method used to get the full list of issue IDs that area available in the
     * system.
     *
     * @access  public
     * @param   string $extra_condition An extra condition in the WHERE clause
     * @return  array The list of issue IDs
     */
    function getColList($extra_condition = NULL)
    {
        $stmt = "SELECT
                    iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_prj_id=" . Auth::getCurrentProject();
        if (!empty($extra_condition)) {
            $stmt .= " AND $extra_condition ";
        }
        $stmt .= "
                 ORDER BY
                    iss_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full list of issue IDs and their respective 
     * titles.
     *
     * @access  public
     * @param   string $extra_condition An extra condition in the WHERE clause
     * @return  array The list of issues
     */
    function getAssocList($extra_condition = NULL)
    {
        $stmt = "SELECT
                    iss_id,
                    iss_summary
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_prj_id=" . Auth::getCurrentProject();
        if (!empty($extra_condition)) {
            $stmt .= " AND $extra_condition ";
        }
        $stmt .= "
                 ORDER BY
                    iss_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of issues associated to a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of associated issues
     */
    function getAssociatedIssues($issue_id)
    {
        $stmt = "SELECT
                    isa_associated_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_association
                 WHERE
                    isa_issue_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to check whether an issue was already closed or not.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  boolean
     */
    function isClosed($issue_id)
    {
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    iss_id=$issue_id AND
                    iss_sta_id=sta_id AND
                    sta_is_closed=1";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($res == 0) {
                return false;
            } else {
                return true;
            }
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Issue Class');
}
?>