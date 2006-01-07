<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
include_once(APP_INC_PATH . "class.round_robin.php");
include_once(APP_INC_PATH . "class.authorized_replier.php");
include_once(APP_INC_PATH . "class.workflow.php");
include_once(APP_INC_PATH . "class.priority.php");
include_once(APP_INC_PATH . "class.reminder_action.php");
include_once(APP_INC_PATH . "class.search_profile.php");
include_once(APP_INC_PATH . "class.session.php");

/**
 * Class designed to handle all business logic related to the issues in the
 * system, such as adding or updating them or listing them in the grid mode.
 *
 * @author  João Prado Maia <jpm@mysql.com>
 * @version $Revision: 1.114 $
 */

class Issue
{
    /**
     * Method used to check whether a given issue ID exists or not.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   boolean $check_project If we should check that this issue is in the current project
     * @return  boolean
     */
    function exists($issue_id, $check_project = true)
    {
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id=" . Misc::escapeInteger($issue_id);
        if ($check_project) {
            $stmt .= " AND
                    iss_prj_id = " . Auth::getCurrentProject();
        }
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
     * Method used to get the list of column heading titles for the
     * CSV export functionality of the issue listing screen.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of column heading titles
     */
    function getColumnHeadings($prj_id)
    {
        $headings = array(
            'Priority',
            'Issue ID'
        );
        // hide the group column from the output if no
        // groups are available in the database
        $groups = Group::getAssocList($prj_id);
        if (count($groups) > 0) {
            $headings[] = 'Group';
        }
        $headings[] = 'Assigned';
        $headings[] = 'Time Spent';
        // hide the category column from the output if no
        // categories are available in the database
        $categories = Category::getAssocList($prj_id);
        if (count($categories) > 0) {
            $headings[] = 'Category';
        }
        if (Customer::hasCustomerIntegration($prj_id)) {
            $headings[] = 'Customer';
        }
        $headings[] = 'Status';
        $headings[] = 'Status Change Date';
        $headings[] = 'Last Action Date';
        $headings[] = 'Summary';
        return $headings;
    }


    /**
     * Method used to get the full list of date fields available to issues, to
     * be used when customizing the issue listing screen in the 'last status
     * change date' column.
     *
     * @access  public
     * @param   boolean $display_customer_fields Whether to include any customer related fields or not
     * @return  array The list of available date fields
     */
    function getDateFieldsAssocList($display_customer_fields = FALSE)
    {
        $fields = array(
            'iss_created_date'              => 'Created Date',
            'iss_updated_date'              => 'Last Updated Date',
            'iss_last_response_date'        => 'Last Response Date',
            'iss_closed_date'               => 'Closed Date'
        );
        if ($display_customer_fields) {
            $fields['iss_last_customer_action_date'] = 'Customer Action Date';
        }
        asort($fields);
        return $fields;
    }


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
                    iss_prj_id=" . Misc::escapeInteger($prj_id) . "
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
        static $returns;

        $issue_id = Misc::escapeInteger($issue_id);

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

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
            $returns[$issue_id] = $res;
            return $res;
        }
    }


    /**
     * Records the last customer action date for a given issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function recordLastCustomerAction($issue_id)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_last_customer_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_public_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_public_action_type='customer action'
                 WHERE
                    iss_id=" . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Returns the customer ID associated with the given issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The customer ID associated with the issue
     */
    function getCustomerID($issue_id)
    {
        static $returns;

        $issue_id = Misc::escapeInteger($issue_id);

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = "SELECT
                    iss_customer_id
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
     * Returns the customer ID associated with the given issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The customer ID associated with the issue
     */
    function getContactID($issue_id)
    {
        static $returns;

        $issue_id = Misc::escapeInteger($issue_id);

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = "SELECT
                    iss_customer_contact_id
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
     * Method used to get the project associated to a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The project ID
     */
    function getProjectID($issue_id)
    {
        static $returns;

        $issue_id = Misc::escapeInteger($issue_id);

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
        Workflow::handleAssignmentChange(Issue::getProjectID($issue_id), $issue_id, $usr_id, Issue::getDetails($issue_id), array($assignee), true);
        // clear up the assignments for this issue, and then assign it to the current user
        Issue::deleteUserAssociations($issue_id, $usr_id);
        $res = Issue::addUserAssociation($usr_id, $issue_id, $assignee, false);
        if ($res != -1) {
            // save a history entry about this...
            History::add($issue_id, $usr_id, History::getTypeID('remote_assigned'), "Issue remotely assigned to " . User::getFullName($assignee) . " by " . User::getFullName($usr_id));
            Notification::subscribeUser($usr_id, $issue_id, $assignee, Notification::getDefaultActions(), false);
            if ($assignee != $usr_id) {
                Notification::notifyNewAssignment(array($assignee), $issue_id);
            }
        }
        return $res;
    }


    /**
     * Method used to set the status of a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $status_id The new status ID
     * @param   boolean $notify If a notification should be sent about this change.
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function setStatus($issue_id, $status_id, $notify = false)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $status_id = Misc::escapeInteger($status_id);

        // check if the status is already set to the 'new' one
        if (Issue::getStatusID($issue_id) == $status_id) {
            return -1;
        }

        $old_status = Issue::getStatusID($issue_id);
        $old_details = Status::getDetails($old_status);

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_sta_id=$status_id,
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_public_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_public_action_type='update'
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // clear out the last-triggered-reminder flag when changing the status of an issue
            Reminder_Action::clearLastTriggered($issue_id);

            // if old status was closed and new status is not, clear closed data from issue.
            if (@$old_details['sta_is_closed'] == 1) {
                $new_details = Status::getDetails($status_id);
                if ($new_details['sta_is_closed'] != 1) {
                    Issue::clearClosed($issue_id);
                }
            }

            if ($notify) {
                Notification::notifyStatusChange($issue_id, $old_status, $status_id);
            }

            return 1;
        }
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

        $res = Issue::setStatus($issue_id, $sta_id);
        if ($res == 1) {
            // record history entry
            History::add($issue_id, $usr_id, History::getTypeID('remote_status_change'), "Status remotely changed to '$new_status' by " . User::getFullName($usr_id));
        }
        return $res;
    }


    /**
     * Method used to set the release of an issue
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @param   integer $pre_id The ID of the release to set this issue too
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function setRelease($issue_id, $pre_id)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $pre_id = Misc::escapeInteger($pre_id);

        if ($pre_id != Issue::getRelease($issue_id)) {
            $sql = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    SET
                        iss_pre_id = $pre_id
                    WHERE
                        iss_id = $issue_id";
            $res = $GLOBALS["db_api"]->dbh->query($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                return 1;
            }
        }
    }


    /**
     * Returns the current release of an issue
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @return  integer The release
     */
    function getRelease($issue_id)
    {
        $sql = "SELECT
                    iss_pre_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                WHERE
                    iss_id = " . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } else {
            return $res;
        }
    }


    /**
     * Method used to set the priority of an issue
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @param   integer $pri_id The ID of the priority to set this issue too
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function setPriority($issue_id, $pri_id)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $pri_id = Misc::escapeInteger($pri_id);

        if ($pri_id != Issue::getPriority($issue_id)) {
            $sql = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    SET
                        iss_pri_id = $pri_id
                    WHERE
                        iss_id = $issue_id";
            $res = $GLOBALS["db_api"]->dbh->query($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                return 1;
            }
        }
    }


    /**
     * Returns the current issue priority
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @return  integer The priority
     */
    function getPriority($issue_id)
    {
        $sql = "SELECT
                    iss_pri_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                WHERE
                    iss_id = " . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } else {
            return $res;
        }
    }


    /**
     * Method used to set the category of an issue
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @param   integer $prc_id The ID of the category to set this issue too
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function setCategory($issue_id, $prc_id)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $prc_id = Misc::escapeInteger($prc_id);

        if ($prc_id != Issue::getPriority($issue_id)) {
            $sql = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    SET
                        iss_prc_id = $prc_id
                    WHERE
                        iss_id = $issue_id";
            $res = $GLOBALS["db_api"]->dbh->query($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                return 1;
            }
        }
    }


    /**
     * Returns the current issue category
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @return  integer The category
     */
    function getCategory($issue_id)
    {
        $sql = "SELECT
                    iss_prc_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                WHERE
                    iss_id = " . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } else {
            return $res;
        }
    }


    /**
     * Method used to get all issues associated with a status that doesn't have
     * the 'closed' context.
     *
     * @access  public
     * @param   integer $prj_id The project ID to list issues from
     * @param   integer $usr_id The user ID of the user requesting this information
     * @param   boolean $show_all_issues Whether to show all open issues, or just the ones assigned to the given email address
     * @param   integer $status_id The status ID to be used to restrict results
     * @return  array The list of open issues
     */
    function getOpenIssues($prj_id, $usr_id, $show_all_issues, $status_id)
    {
        $prj_id = Misc::escapeInteger($prj_id);
        $status_id = Misc::escapeInteger($status_id);
        $projects = Project::getRemoteAssocListByUser($usr_id);
        if (@count($projects) == 0) {
            return '';
        }

        $stmt = "SELECT
                    iss_id,
                    iss_summary,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 ON
                    isu_iss_id=iss_id
                 WHERE ";
        if (!empty($status_id)) {
            $stmt .= " sta_id=$status_id AND ";
        }
        $stmt .= "
                    iss_prj_id=$prj_id AND
                    sta_id=iss_sta_id AND
                    sta_is_closed=0";
        if ($show_all_issues == false) {
            $stmt .= " AND
                    isu_usr_id=$usr_id";
        }
        $stmt .= "\nGROUP BY
                        iss_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            if (count($res) > 0) {
                Issue::getAssignedUsersByIssues($res);
            }
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
        $issue_id = Misc::escapeInteger($issue_id);

        $stmt = "SELECT
                    iss_created_date,
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
            $res['reply_subject'] = 'Re: [#' . $issue_id . '] ' . $res["sup_subject"];
            $res['created_date_ts'] = Date_API::getUnixTimestamp($res['iss_created_date'], 'GMT');
            return $res;
        }
    }


    /**
     * Method used to record the last updated timestamp for a given
     * issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $type The type of update that was made (optional)
     * @return  boolean
     */
    function markAsUpdated($issue_id, $type = false)
    {
        $public = array("staff response", "customer action", "file uploaded", "user response");
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "'\n";
        if ($type != false) {
            if (in_array($type, $public)) {
                $field = "iss_last_public_action_";
            } else {
                $field = "iss_last_internal_action_";
            }
            $stmt .= ",\n " . $field . "date = '" . Date_API::getCurrentDateGMT() . "',\n" .
                $field . "type  ='" . Misc::escapeString($type) . "'\n";
        }
        $stmt .= "WHERE
                    iss_id=" . Misc::escapeInteger($issue_id);
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
                    iss_duplicated_iss_id=" . Misc::escapeInteger($issue_id);
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

        $issue_id = Misc::escapeInteger($issue_id);

        $ids = Issue::getDuplicateList($issue_id);
        if ($ids == '') {
            return -1;
        }
        $ids = @array_keys($ids);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_internal_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_internal_action_type='updated',
                    iss_prc_id=" . Misc::escapeInteger($HTTP_POST_VARS["category"]) . ",";
        if (@$HTTP_POST_VARS["keep"] == "no") {
            $stmt .= "iss_pre_id=" . Misc::escapeInteger($HTTP_POST_VARS["release"]) . ",";
        }
        $stmt .= "
                    iss_pri_id=" . Misc::escapeInteger($HTTP_POST_VARS["priority"]) . ",
                    iss_sta_id=" . Misc::escapeInteger($HTTP_POST_VARS["status"]) . ",
                    iss_res_id=" . Misc::escapeInteger($HTTP_POST_VARS["resolution"]) . "
                 WHERE
                    iss_id IN (" . implode(", ", $ids) . ")";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // record the change
            for ($i = 0; $i < count($ids); $i++) {
                History::add($ids[$i], Auth::getUserID(), History::getTypeID('duplicate_update'),
                    "The details for issue #$issue_id were updated by " . User::getFullName(Auth::getUserID()) . " and the changes propagated to the duplicated issues.");
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
        $res = Issue::getDuplicateDetailsList($issue_id);
        if (@count($res) == 0) {
            return '';
        } else {
            $list = array();
            for ($i = 0; $i < count($res); $i++) {
                $list[$res[$i]['issue_id']] = $res[$i]['title'];
            }
            return $list;
        }
    }


    /**
     * Method used to get a list of the duplicate issues (and their details)
     * for a given issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of duplicates
     */
    function getDuplicateDetailsList($issue_id)
    {
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = "SELECT
                    iss_id issue_id,
                    iss_summary title,
                    sta_title current_status,
                    sta_is_closed is_closed
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    iss_sta_id=sta_id AND
                    iss_duplicated_iss_id=" . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            $returns[$issue_id] = $res;
            return $res;
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
        $issue_id = Misc::escapeInteger($issue_id);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_internal_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_internal_action_type='updated',
                    iss_duplicated_iss_id=NULL
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // record the change
            History::add($issue_id, Auth::getUserID(), History::getTypeID('duplicate_removed'), "Duplicate flag was reset by " . User::getFullName(Auth::getUserID()));
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

        $issue_id = Misc::escapeInteger($issue_id);

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_internal_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_internal_action_type='updated',
                    iss_duplicated_iss_id=" . Misc::escapeInteger($HTTP_POST_VARS["duplicated_issue"]) . "
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if (!empty($HTTP_POST_VARS["comments"])) {
                // add note with the comments of marking an issue as a duplicate of another one
                $HTTP_POST_VARS['title'] = 'Issue duplication comments';
                $HTTP_POST_VARS["note"] = $HTTP_POST_VARS["comments"];
                Note::insert(Auth::getUserID(), $issue_id);
            }
            // record the change
            History::add($issue_id, Auth::getUserID(), History::getTypeID('duplicate_added'),
                    "Issue marked as a duplicate of issue #" . $HTTP_POST_VARS["duplicated_issue"] . " by " . User::getFullName(Auth::getUserID()));
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
                    isu_iss_id=" . Misc::escapeInteger($issue_id) . " AND
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
                    iss_id=" . Misc::escapeInteger($issue_id);
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
                    iss_summary='" . Misc::escapeString($summary) . "'";
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
                    iss_last_public_action_date,
                    iss_last_public_action_type,
                    iss_summary,
                    iss_description,
                    iss_root_message_id
                 ) VALUES (
                    " . Misc::escapeInteger($HTTP_POST_VARS["project"]) . ",
                    " . $options["category"] . ",
                    0,
                    " . $options["priority"] . ",
                    " . $options["reporter"] . ",";
        if (!empty($initial_status)) {
            $stmt .= "$initial_status,";
        }
        $stmt .= "
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Date_API::getCurrentDateGMT() . "',
                    'created',
                    '" . Misc::escapeString($HTTP_POST_VARS["summary"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["description"]) . "',
                    '" . Misc::escapeString(Mail_API::generateMessageID()) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return $res;
        } else {
            $new_issue_id = $GLOBALS["db_api"]->get_last_insert_id();
            // log the creation of the issue
            History::add($new_issue_id, APP_SYSTEM_USER_ID, History::getTypeID('issue_opened_anon'), 'Issue opened anonymously');

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

            // now add the user/issue association
            $assign = array();
            $users = @$options["users"];
            $actions = Notification::getDefaultActions();
            for ($i = 0; $i < count($users); $i++) {
                Notification::subscribeUser(APP_SYSTEM_USER_ID, $new_issue_id, $users[$i], $actions);
                Issue::addUserAssociation(APP_SYSTEM_USER_ID, $new_issue_id, $users[$i]);
                $assign[] = $users[$i];
            }

            // also notify any users that want to receive emails anytime a new issue is created
            Notification::notifyNewIssue($HTTP_POST_VARS['project'], $new_issue_id);

            Workflow::handleNewIssue(Misc::escapeInteger($HTTP_POST_VARS["project"]),  $new_issue_id, false, false);

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
        $items = @implode(", ", Misc::escapeInteger($ids));
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
            if (count($res) > 0) {
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
            }
            return true;
        }
    }


    /**
     * Method used to close off an issue.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   integer $issue_id The issue ID
     * @param   bool $send_notification Whether to send a notification about this action or not
     * @param   integer $resolution_id The resolution ID
     * @param   integer $status_id The status ID
     * @param   string $reason The reason for closing this issue
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function close($usr_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason)
    {
        global $HTTP_POST_VARS;

        $usr_id = Misc::escapeInteger($usr_id);
        $issue_id = Misc::escapeInteger($issue_id);
        $resolution_id = Misc::escapeInteger($resolution_id);
        $status_id = Misc::escapeInteger($status_id);

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_public_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_public_action_type='closed',
                    iss_closed_date='" . Date_API::getCurrentDateGMT() . "',\n";
        if (!empty($resolution_id)) {
            $stmt .= "iss_res_id=$resolution_id,\n";
        }
        $stmt .= "iss_sta_id=$status_id
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $prj_id = Issue::getProjectID($issue_id);

            // add note with the reason to close the issue
            $HTTP_POST_VARS['title'] = 'Issue closed comments';
            $HTTP_POST_VARS["note"] = $reason;
            Note::insert($usr_id, $issue_id, false, true, true);
            // record the change
            History::add($issue_id, $usr_id, History::getTypeID('issue_closed'), "Issue updated to status '" . Status::getStatusTitle($status_id) . "' by " . User::getFullName($usr_id));
            if ($send_notification) {
                if (Customer::hasCustomerIntegration($prj_id)) {
                    // send a special confirmation email when customer issues are closed
                    $stmt = "SELECT
                                iss_customer_contact_id
                             FROM
                                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                             WHERE
                                iss_id=$issue_id";
                    $customer_contact_id = $GLOBALS["db_api"]->dbh->getOne($stmt);
                    if (!empty($customer_contact_id)) {
                        Customer::notifyIssueClosed($prj_id, $issue_id, $customer_contact_id);
                    }
                }
                // send notifications for the issue being closed
                Notification::notify($issue_id, 'closed');
            }
            Workflow::handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason);
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

        $issue_id = Misc::escapeInteger($issue_id);

        $usr_id = Auth::getUserID();
        $prj_id = Issue::getProjectID($issue_id);
        // get all of the 'current' information of this issue
        $current = Issue::getDetails($issue_id);
        // update the issue associations
        $association_diff = Misc::arrayDiff($current['associated_issues'], @$HTTP_POST_VARS['associated_issues']);
        if (count($association_diff) > 0) {
            // go through the new assocations, if association already exists, skip it
            $associations_to_remove = $current['associated_issues'];
            if (count(@$HTTP_POST_VARS['associated_issues']) > 0) {
                foreach ($HTTP_POST_VARS['associated_issues'] as $index => $associated_id) {
                    if (!in_array($associated_id, $current['associated_issues'])) {
                        Issue::addAssociation($issue_id, $associated_id, $usr_id);
                    } else {
                        // already assigned, remove this user from list of users to remove
                        unset($associations_to_remove[array_search($associated_id, $associations_to_remove)]);
                    }
                }
            }
            if (count($associations_to_remove) > 0) {
                foreach ($associations_to_remove as $associated_id) {
                    Issue::deleteAssociation($issue_id, $associated_id);
                }
            }
        }
        if ((!empty($HTTP_POST_VARS['expected_resolution_date']['Year'])) &&
             (!empty($HTTP_POST_VARS['expected_resolution_date']['Month'])) &&
             (!empty($HTTP_POST_VARS['expected_resolution_date']['Day']))) {
            $HTTP_POST_VARS['expected_resolution_date'] = sprintf('%s-%s-%s', $HTTP_POST_VARS['expected_resolution_date']['Year'],
                                                $HTTP_POST_VARS['expected_resolution_date']['Month'],
                                                $HTTP_POST_VARS['expected_resolution_date']['Day']);
        } else {
            $HTTP_POST_VARS['expected_resolution_date'] = '';
        }
        $assignments_changed = false;
        if (@$HTTP_POST_VARS["keep_assignments"] == "no") {
            // only change the issue-user associations if there really were any changes
            $old_assignees = array_merge($current['assigned_users'], $current['assigned_inactive_users']);
            if (!empty($HTTP_POST_VARS['assignments'])) {
                $new_assignees = @$HTTP_POST_VARS['assignments'];
            } else {
                $new_assignees = array();
            }
            $assignment_notifications = array();

            // remove people from the assignment list, if appropriate
            foreach ($old_assignees as $assignee) {
                if (!in_array($assignee, $new_assignees)) {
                    Issue::deleteUserAssociation($issue_id, $assignee);
                    $assignments_changed = true;
                }
            }
            // add people to the assignment list, if appropriate
            foreach ($new_assignees as $assignee) {
                if (!in_array($assignee, $old_assignees)) {
                    Issue::addUserAssociation($usr_id, $issue_id, $assignee);
                    Notification::subscribeUser($usr_id, $issue_id, $assignee, Notification::getDefaultActions(), TRUE);
                    $assignment_notifications[] = $assignee;
                    $assignments_changed = true;
                }
            }
            if (count($assignment_notifications) > 0) {
                Notification::notifyNewAssignment($assignment_notifications, $issue_id);
            }
        }
        if (empty($HTTP_POST_VARS["estimated_dev_time"])) {
            $HTTP_POST_VARS["estimated_dev_time"] = 0;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_public_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_public_action_type='updated',";
        if (!empty($HTTP_POST_VARS["category"])) {
            $stmt .= "iss_prc_id=" . Misc::escapeInteger($HTTP_POST_VARS["category"]) . ",";
        }
        if (@$HTTP_POST_VARS["keep"] == "no") {
            $stmt .= "iss_pre_id=" . Misc::escapeInteger($HTTP_POST_VARS["release"]) . ",";
        }
        if (!empty($HTTP_POST_VARS['expected_resolution_date'])) {
            $stmt .= "iss_expected_resolution_date='" . Misc::escapeString($HTTP_POST_VARS['expected_resolution_date']) . "',";
        } else {
            $stmt .= "iss_expected_resolution_date=null,";
        }
        $stmt .= "
                    iss_pre_id=" . Misc::escapeInteger($HTTP_POST_VARS["release"]) . ",
                    iss_pri_id=" . Misc::escapeInteger($HTTP_POST_VARS["priority"]) . ",
                    iss_sta_id=" . Misc::escapeInteger($HTTP_POST_VARS["status"]) . ",
                    iss_res_id=" . Misc::escapeInteger($HTTP_POST_VARS["resolution"]) . ",
                    iss_summary='" . Misc::escapeString($HTTP_POST_VARS["summary"]) . "',
                    iss_description='" . Misc::escapeString($HTTP_POST_VARS["description"]) . "',
                    iss_dev_time='" . Misc::escapeString($HTTP_POST_VARS["estimated_dev_time"]) . "',
                    iss_percent_complete= '" . Misc::escapeString($HTTP_POST_VARS["percent_complete"]) . "',
                    iss_trigger_reminders=" . Misc::escapeInteger($HTTP_POST_VARS["trigger_reminders"]) . ",
                    iss_grp_id ='" . Misc::escapeInteger($HTTP_POST_VARS["group"]) . "'";
        if (isset($HTTP_POST_VARS['private'])) {
            $stmt .= ",
                    iss_private = " . Misc::escapeInteger($HTTP_POST_VARS['private']);
        }
        $stmt .= "
                 WHERE
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // add change to the history (only for changes on specific fields?)
            $updated_fields = array();
            if ($current["iss_expected_resolution_date"] != $HTTP_POST_VARS['expected_resolution_date']) {
                $updated_fields["Expected Resolution Date"] = History::formatChanges($current["iss_expected_resolution_date"], $HTTP_POST_VARS['expected_resolution_date']);
            }
            if ($current["iss_prc_id"] != $HTTP_POST_VARS["category"]) {
                $updated_fields["Category"] = History::formatChanges(Category::getTitle($current["iss_prc_id"]), Category::getTitle($HTTP_POST_VARS["category"]));
            }
            if ($current["iss_pre_id"] != $HTTP_POST_VARS["release"]) {
                $updated_fields["Release"] = History::formatChanges(Release::getTitle($current["iss_pre_id"]), Release::getTitle($HTTP_POST_VARS["release"]));
            }
            if ($current["iss_pri_id"] != $HTTP_POST_VARS["priority"]) {
                $updated_fields["Priority"] = History::formatChanges(Priority::getTitle($current["iss_pri_id"]), Priority::getTitle($HTTP_POST_VARS["priority"]));
                Workflow::handlePriorityChange($prj_id, $issue_id, $usr_id, $current, $HTTP_POST_VARS);
            }
            if ($current["iss_sta_id"] != $HTTP_POST_VARS["status"]) {
                // clear out the last-triggered-reminder flag when changing the status of an issue
                Reminder_Action::clearLastTriggered($issue_id);

                // if old status was closed and new status is not, clear closed data from issue.
                $old_status_details = Status::getDetails($current['iss_sta_id']);
                if ($old_status_details['sta_is_closed'] == 1) {
                    $new_status_details = Status::getDetails($HTTP_POST_VARS["status"]);
                    if ($new_status_details['sta_is_closed'] != 1) {
                        Issue::clearClosed($issue_id);
                    }
                }
                $updated_fields["Status"] = History::formatChanges(Status::getStatusTitle($current["iss_sta_id"]), Status::getStatusTitle($HTTP_POST_VARS["status"]));
            }
            if ($current["iss_res_id"] != $HTTP_POST_VARS["resolution"]) {
                $updated_fields["Resolution"] = History::formatChanges(Resolution::getTitle($current["iss_res_id"]), Resolution::getTitle($HTTP_POST_VARS["resolution"]));
            }
            if ($current["iss_dev_time"] != $HTTP_POST_VARS["estimated_dev_time"]) {
                $updated_fields["Estimated Dev. Time"] = History::formatChanges(Misc::getFormattedTime(($current["iss_dev_time"]*60)), Misc::getFormattedTime(($HTTP_POST_VARS["estimated_dev_time"]*60)));
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
                History::add($issue_id, $usr_id, History::getTypeID('issue_updated'), "Issue updated ($changes) by " . User::getFullName($usr_id));
                // send notifications for the issue being updated
                Notification::notifyIssueUpdated($issue_id, $current, $HTTP_POST_VARS);
            }

            // record group change as a seperate change
            if ($current["iss_grp_id"] != $HTTP_POST_VARS["group"]) {
                History::add($issue_id, $usr_id, History::getTypeID('group_changed'),
                    "Group changed (" . History::formatChanges(Group::getName($current["iss_grp_id"]), Group::getName($HTTP_POST_VARS["group"])) . ") by " . User::getFullName($usr_id));
            }

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

            // if there is customer integration, mark last customer action
            if ((Customer::hasCustomerIntegration($prj_id)) && (User::getRoleByUser($usr_id, $prj_id) == User::getRoleID('Customer'))) {
                Issue::recordLastCustomerAction($issue_id);
            }

            if ($assignments_changed) {
                // XXX: we may want to also send the email notification for those "new" assignees
                Workflow::handleAssignmentChange(Issue::getProjectID($issue_id), $issue_id, $usr_id, Issue::getDetails($issue_id), @$HTTP_POST_VARS['assignments'], false);
            }

            Workflow::handleIssueUpdated($prj_id, $issue_id, $usr_id, $current, $HTTP_POST_VARS);
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
    function addAssociation($issue_id, $associated_id, $usr_id, $link_issues = TRUE)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $associated_id = Misc::escapeInteger($associated_id);

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
        History::add($issue_id, $usr_id, History::getTypeID('issue_associated'), "Issue associated to #$associated_id by " . User::getFullName($usr_id));
        // link the associated issue back to this one
        if ($link_issues) {
            Issue::addAssociation($associated_id, $issue_id, $usr_id, FALSE);
        }
    }


    /**
     * Method used to remove the issue associations related to a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  void
     */
    function deleteAssociations($issue_id, $usr_id = FALSE)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        if (is_array($issue_id)) {
            $issue_id = implode(", ", $issue_id);
        }
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_association
                 WHERE
                    isa_issue_id IN ($issue_id) OR
                    isa_associated_id IN ($issue_id)";
        $GLOBALS["db_api"]->dbh->query($stmt);
        if ($usr_id) {
            History::add($issue_id, $usr_id, History::getTypeID('issue_all_unassociated'), 'Issue associations removed by ' . User::getFullName($usr_id));
        }
    }


    /**
     * Method used to remove a issue association from an issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $associated_id The associated issue ID to remove.
     * @return  void
     */
    function deleteAssociation($issue_id, $associated_id)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $associated_id = Misc::escapeInteger($associated_id);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_association
                 WHERE
                    (
                        isa_issue_id = $issue_id AND
                        isa_associated_id = $associated_id
                    ) OR
                    (
                        isa_issue_id = $associated_id AND
                        isa_associated_id = $issue_id
                    )";
        $GLOBALS["db_api"]->dbh->query($stmt);
        History::add($issue_id, Auth::getUserID(), History::getTypeID('issue_unassociated'),
                "Issue association #$associated_id removed by " . User::getFullName(Auth::getUserID()));
        History::add($associated_id, Auth::getUserID(), History::getTypeID('issue_unassociated'),
                "Issue association #$issue_id removed by " . User::getFullName(Auth::getUserID()));
    }


    /**
     * Method used to assign an issue with an user.
     *
     * @access  public
     * @param   integer $usr_id The user ID of the person performing this change
     * @param   integer $issue_id The issue ID
     * @param   integer $assignee_usr_id The user ID of the assignee
     * @param   boolean $add_history Whether to add a history entry about this or not
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function addUserAssociation($usr_id, $issue_id, $assignee_usr_id, $add_history = TRUE)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $assignee_usr_id = Misc::escapeInteger($assignee_usr_id);
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 (
                    isu_iss_id,
                    isu_usr_id,
                    isu_assigned_date
                 ) VALUES (
                    $issue_id,
                    $assignee_usr_id,
                    '" . Date_API::getCurrentDateGMT() . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if ($add_history) {
                History::add($issue_id, $usr_id, History::getTypeID('user_associated'),
                    'Issue assigned to ' . User::getFullName($assignee_usr_id) . ' by ' . User::getFullName($usr_id));
            }
            return 1;
        }
    }


    /**
     * Method used to delete all user assignments for a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID of the person performing the change
     * @return  void
     */
    function deleteUserAssociations($issue_id, $usr_id = FALSE)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        if (is_array($issue_id)) {
            $issue_id = implode(", ", $issue_id);
        }
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 WHERE
                    isu_iss_id IN ($issue_id)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if ($usr_id) {
                History::add($issue_id, $usr_id, History::getTypeID('user_all_unassociated'), 'Issue assignments removed by ' . User::getFullName($usr_id));
            }
            return 1;
        }
    }


    /**
     * Method used to delete a single user assignments for a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user to remove.
     * @param   boolean $add_history Whether to add a history entry about this or not
     * @return  void
     */
    function deleteUserAssociation($issue_id, $usr_id, $add_history = true)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $usr_id = Misc::escapeInteger($usr_id);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 WHERE
                    isu_iss_id = $issue_id AND
                    isu_usr_id = $usr_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if ($add_history) {
                History::add($issue_id, Auth::getUserID(), History::getTypeID('user_unassociated'),
                    User::getFullName($usr_id) . ' removed from issue by ' . User::getFullName(Auth::getUserID()));
            }
            return 1;
        }
    }


    /**
     * Creates an issue with the given email information.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $usr_id The user responsible for this action
     * @param   string $sender The original sender of this email
     * @param   string $summary The issue summary
     * @param   string $description The issue description
     * @param   integer $category The category ID
     * @param   integer $priority The priority ID
     * @param   array $assignment The list of users to assign this issue to
     * @param   string $date The date the email was originally sent.
     * @param   string $msg_id The message ID of the email we are creating this issue from.
     * @return  void
     */
    function createFromEmail($prj_id, $usr_id, $sender, $summary, $description, $category, $priority, $assignment, $date, $msg_id)
    {
        $exclude_list = array();
        $sender_email = Mail_API::getEmailAddress($sender);
        $sender_usr_id = User::getUserIDByEmail($sender_email);
        if (!empty($sender_usr_id)) {
            $reporter = $sender_usr_id;
            $exclude_list[] = $sender_usr_id;
        } else {
            $reporter = APP_SYSTEM_USER_ID;
        }
        if (Customer::hasCustomerIntegration($prj_id)) {
            list($customer_id, $customer_contact_id) = Customer::getCustomerIDByEmails($prj_id, array($sender_email));
            if (!empty($customer_id)) {
                $contact = Customer::getContactDetails($prj_id, $customer_contact_id);
                // overwrite the reporter with the customer contact
                $reporter = User::getUserIDByContactID($customer_contact_id);
                $contact_timezone = Date_API::getPreferredTimezone($reporter);
            }
        } else {
            $customer_id = FALSE;
        }

        $initial_status = Project::getInitialStatus($prj_id);
        // add new issue
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 (
                    iss_prj_id,\n";
        if (!empty($category)) {
            $stmt .= "iss_prc_id,\n";
        }
        $stmt .= "iss_pri_id,
                    iss_usr_id,";
        if (!empty($initial_status)) {
            $stmt .= "iss_sta_id,";
        }
        if (!empty($customer_id)) {
            $stmt .= "
                    iss_customer_id,
                    iss_customer_contact_id,
                    iss_contact_person_lname,
                    iss_contact_person_fname,
                    iss_contact_email,
                    iss_contact_phone,
                    iss_contact_timezone,";
        }
        $stmt .= "
                    iss_created_date,
                    iss_last_public_action_date,
                    iss_last_public_action_type,
                    iss_summary,
                    iss_description,
                    iss_root_message_id
                 ) VALUES (
                    " . $prj_id . ",\n";
        if (!empty($category)) {
            $stmt .=  Misc::escapeInteger($category) . ",\n";
        }
        $stmt .= Misc::escapeInteger($priority) . ",
                    " . Misc::escapeInteger($reporter) . ",";
        if (!empty($initial_status)) {
            $stmt .= Misc::escapeInteger($initial_status) . ",";
        }
        if (!empty($customer_id)) {
            $stmt .= "
                    " . Misc::escapeInteger($customer_id) . ",
                    " . Misc::escapeInteger($customer_contact_id) . ",
                    '" . Misc::escapeString($contact['last_name']) . "',
                    '" . Misc::escapeString($contact['first_name']) . "',
                    '" . Misc::escapeString($sender_email) . "',
                    '" . Misc::escapeString($contact['phone']) . "',
                    '" . Misc::escapeString($contact_timezone) . "',";
        }
        $stmt .= "
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Date_API::getCurrentDateGMT() . "',
                    'created',
                    '" . Misc::escapeString($summary) . "',
                    '" . Misc::escapeString($description) . "',
                    '" . Misc::escapeString($msg_id) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_issue_id = $GLOBALS["db_api"]->get_last_insert_id();
            $has_TAM = false;
            $has_RR = false;
            // log the creation of the issue
            History::add($new_issue_id, $usr_id, History::getTypeID('issue_opened'), 'Issue opened by ' . $sender);

            $emails = array();
            $manager_usr_ids = array();
            if ((Customer::hasCustomerIntegration($prj_id)) && (!empty($customer_id))) {
                // if there are any technical account managers associated with this customer, add these users to the notification list
                $managers = Customer::getAccountManagers($prj_id, $customer_id);
                $manager_usr_ids = array_keys($managers);
                $manager_emails = array_values($managers);
                $emails = array_merge($emails, $manager_emails);
            }
            // add the reporter to the notification list
            $emails[] = $sender;
            $emails = array_unique($emails); // COMPAT: version >= 4.0.1
            $actions = Notification::getDefaultActions();
            foreach ($emails as $address) {
                Notification::subscribeEmail($reporter, $new_issue_id, $address, $actions);
            }

            // only assign the issue to an user if the associated customer has any technical account managers
            $users = array();
            if ((Customer::hasCustomerIntegration($prj_id)) && (count($manager_usr_ids) > 0)) {
                foreach ($manager_usr_ids as $manager_usr_id) {
                    $users[] = $manager_usr_id;
                    Issue::addUserAssociation(APP_SYSTEM_USER_ID, $new_issue_id, $manager_usr_id, false);
                    History::add($new_issue_id, $usr_id, History::getTypeID('issue_auto_assigned'), 'Issue auto-assigned to ' . User::getFullName($manager_usr_id) . ' (TAM)');
                }
                $has_TAM = true;
            }
            // now add the user/issue association
            if (@count($assignment) > 0) {
                for ($i = 0; $i < count($assignment); $i++) {
                    Notification::subscribeUser($reporter, $new_issue_id, $assignment[$i], $actions);
                    Issue::addUserAssociation(APP_SYSTEM_USER_ID, $new_issue_id, $assignment[$i]);
                    if ($assignment[$i] != $usr_id) {
                        $users[] = $assignment[$i];
                    }
                }
            } else {
                // only use the round-robin feature if this new issue was not
                // already assigned to a customer account manager
                if (@count($manager_usr_ids) < 1) {
                    $assignee = Round_Robin::getNextAssignee($prj_id);
                    // assign the issue to the round robin person
                    if (!empty($assignee)) {
                        Issue::addUserAssociation(APP_SYSTEM_USER_ID, $new_issue_id, $assignee, false);
                        History::add($new_issue_id, APP_SYSTEM_USER_ID, History::getTypeID('rr_issue_assigned'), 'Issue auto-assigned to ' . User::getFullName($assignee) . ' (RR)');
                        $users[] = $assignee;
                        $has_RR = true;
                    }
                }
            }
            if (count($users) > 0) {
                $has_assignee = true;
            }

            // send special 'an issue was auto-created for you' notification back to the sender
            Notification::notifyAutoCreatedIssue($prj_id, $new_issue_id, $sender, $date, $summary);
            // also notify any users that want to receive emails anytime a new issue is created
            Notification::notifyNewIssue($prj_id, $new_issue_id, $exclude_list);

            Workflow::handleNewIssue($prj_id, $new_issue_id, $has_TAM, $has_RR);

            return $new_issue_id;
        }
    }


    /**
     * Method used to add a new issue using the normal report form.
     *
     * @access  public
     * @return  integer The new issue ID
     */
    function insert()
    {
        global $HTTP_POST_VARS, $HTTP_POST_FILES, $insert_errors;

        $usr_id = Auth::getUserID();
        $prj_id = Auth::getCurrentProject();
        $initial_status = Project::getInitialStatus($prj_id);

        $insert_errors = array();

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

        // add new issue
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 (
                    iss_prj_id,\n";
        if (!empty($HTTP_POST_VARS["group"])) {
            $stmt .= "iss_grp_id,\n";
        }
        if (!empty($HTTP_POST_VARS["category"])) {
            $stmt .= "iss_prc_id,\n";
        }
        if (!empty($HTTP_POST_VARS["release"])) {
            $stmt .= "iss_pre_id,\n";
        }
        if (!empty($HTTP_POST_VARS["priority"])) {
            $stmt .= "iss_pri_id,\n";
        }
        $stmt .= "iss_usr_id,";
        if (!empty($initial_status)) {
            $stmt .= "iss_sta_id,";
        }
        if (Customer::hasCustomerIntegration($prj_id)) {
            $stmt .= "
                    iss_customer_id,
                    iss_customer_contact_id,
                    iss_contact_person_lname,
                    iss_contact_person_fname,
                    iss_contact_email,
                    iss_contact_phone,
                    iss_contact_timezone,";
        }
        $stmt .= "
                    iss_created_date,
                    iss_last_public_action_date,
                    iss_last_public_action_type,
                    iss_summary,
                    iss_description,
                    iss_dev_time,
                    iss_private,
                    iss_root_message_id
                 ) VALUES (
                    " . $prj_id . ",\n";
        if (!empty($HTTP_POST_VARS["group"])) {
            $stmt .= Misc::escapeInteger($HTTP_POST_VARS["group"]) . ",\n";
        }
        if (!empty($HTTP_POST_VARS["category"])) {
            $stmt .= Misc::escapeInteger($HTTP_POST_VARS["category"]) . ",\n";
        }
        if (!empty($HTTP_POST_VARS["release"])) {
            $stmt .= Misc::escapeInteger($HTTP_POST_VARS["release"]) . ",\n";
        }
        if (!empty($HTTP_POST_VARS["priority"])) {
            $stmt .= Misc::escapeInteger($HTTP_POST_VARS["priority"]) . ",";
        }
        // if we are creating an issue for a customer, put the
        // main customer contact as the reporter for it
        if (Customer::hasCustomerIntegration($prj_id)) {
            $contact_usr_id = User::getUserIDByContactID($HTTP_POST_VARS['contact']);
            if (empty($contact_usr_id)) {
                $contact_usr_id = $usr_id;
            }
            $stmt .= Misc::escapeInteger($contact_usr_id) . ",";
        } else {
            $stmt .= $usr_id . ",";
        }
        if (!empty($initial_status)) {
            $stmt .= Misc::escapeInteger($initial_status) . ",";
        }
        if (Customer::hasCustomerIntegration($prj_id)) {
            $stmt .= "
                    " . Misc::escapeInteger($HTTP_POST_VARS['customer']) . ",
                    " . Misc::escapeInteger($HTTP_POST_VARS['contact']) . ",
                    '" . Misc::escapeString($HTTP_POST_VARS["contact_person_lname"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["contact_person_fname"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["contact_email"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["contact_phone"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["contact_timezone"]) . "',";
        }
        $stmt .= "
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Date_API::getCurrentDateGMT() . "',
                    'created',
                    '" . Misc::escapeString($HTTP_POST_VARS["summary"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["description"]) . "',
                    " . Misc::escapeString($HTTP_POST_VARS["estimated_dev_time"]) . ",
                    " . Misc::escapeInteger($HTTP_POST_VARS["private"]) . " ,
                    '" . Misc::escapeString(Mail_API::generateMessageID()) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_issue_id = $GLOBALS["db_api"]->get_last_insert_id();
            $has_TAM = false;
            $has_RR = false;
            $info = User::getNameEmail($usr_id);
            // log the creation of the issue
            History::add($new_issue_id, Auth::getUserID(), History::getTypeID('issue_opened'), 'Issue opened by ' . User::getFullName(Auth::getUserID()));

            $emails = array();
            if (Customer::hasCustomerIntegration($prj_id)) {
                if (@count($HTTP_POST_VARS['contact_extra_emails']) > 0) {
                    $emails = $HTTP_POST_VARS['contact_extra_emails'];
                }
                // add the primary contact to the notification list
                if ($HTTP_POST_VARS['add_primary_contact'] == 'yes') {
                    $contact_email = User::getEmailByContactID($HTTP_POST_VARS['contact']);
                    if (!empty($contact_email)) {
                        $emails[] = $contact_email;
                    }
                }
                // if there are any technical account managers associated with this customer, add these users to the notification list
                $managers = Customer::getAccountManagers($prj_id, $HTTP_POST_VARS['customer']);
                $manager_usr_ids = array_keys($managers);
                $manager_emails = array_values($managers);
                $emails = array_merge($emails, $manager_emails);
            }
            // add the reporter to the notification list
            $emails[] = $info['usr_email'];
            $emails = array_unique($emails); // COMPAT: version >= 4.0.1
            $actions = Notification::getDefaultActions();
            foreach ($emails as $address) {
                Notification::subscribeEmail($usr_id, $new_issue_id, $address, $actions);
            }

            // only assign the issue to an user if the associated customer has any technical account managers
            $users = array();
            $has_TAM = false;
            if ((Customer::hasCustomerIntegration($prj_id)) && (count($manager_usr_ids) > 0)) {
                foreach ($manager_usr_ids as $manager_usr_id) {
                    $users[] = $manager_usr_id;
                    Issue::addUserAssociation($usr_id, $new_issue_id, $manager_usr_id, false);
                    History::add($new_issue_id, $usr_id, History::getTypeID('issue_auto_assigned'), 'Issue auto-assigned to ' . User::getFullName($manager_usr_id) . ' (TAM)');
                }
                $has_TAM = true;
            }
            // now add the user/issue association (aka assignments)
            if (@count($HTTP_POST_VARS["users"]) > 0) {
                for ($i = 0; $i < count($HTTP_POST_VARS["users"]); $i++) {
                    Notification::subscribeUser($usr_id, $new_issue_id, $HTTP_POST_VARS["users"][$i], $actions);
                    Issue::addUserAssociation($usr_id, $new_issue_id, $HTTP_POST_VARS["users"][$i]);
                    if ($HTTP_POST_VARS["users"][$i] != $usr_id) {
                        $users[] = $HTTP_POST_VARS["users"][$i];
                    }
                }
            } else {
                // only use the round-robin feature if this new issue was not
                // already assigned to a customer account manager
                if (@count($manager_usr_ids) < 1) {
                    $assignee = Round_Robin::getNextAssignee($prj_id);
                    // assign the issue to the round robin person
                    if (!empty($assignee)) {
                        $users[] = $assignee;
                        Issue::addUserAssociation($usr_id, $new_issue_id, $assignee, false);
                        History::add($new_issue_id, APP_SYSTEM_USER_ID, History::getTypeID('rr_issue_assigned'), 'Issue auto-assigned to ' . User::getFullName($assignee) . ' (RR)');
                        $has_RR = true;
                    }
                }
            }

            // now process any files being uploaded
            $found = 0;
            for ($i = 0; $i < count(@$HTTP_POST_FILES["file"]["name"]); $i++) {
                if (!@empty($HTTP_POST_FILES["file"]["name"][$i])) {
                    $found = 1;
                    break;
                }
            }
            if ($found) {
                $files = array();
                for ($i = 0; $i < count($HTTP_POST_FILES["file"]["name"]); $i++) {
                    $filename = @$HTTP_POST_FILES["file"]["name"][$i];
                    if (empty($filename)) {
                        continue;
                    }
                    $blob = Misc::getFileContents($HTTP_POST_FILES["file"]["tmp_name"][$i]);
                    if (empty($blob)) {
                        // error reading a file
                        $insert_errors["file[$i]"] = "There was an error uploading the file '$filename'.";
                        continue;
                    }
                    $files[] = array(
                        "filename" => $filename,
                        "type"     => $HTTP_POST_FILES['file']['type'][$i],
                        "blob"     => $blob
                    );
                }
                if (count($files) > 0) {
                    $attachment_id = Attachment::add($new_issue_id, $usr_id, 'Files uploaded at issue creation time');
                    foreach ($files as $file) {
                        Attachment::addFile($attachment_id, $new_issue_id, $file["filename"], $file["type"], $file["blob"]);
                    }
                }
            }
            // need to associate any emails ?
            if (!empty($HTTP_POST_VARS["attached_emails"])) {
                $items = explode(",", $HTTP_POST_VARS["attached_emails"]);
                Support::associate($usr_id, $new_issue_id, $items);
            }
            // need to notify any emails being converted into issues ?
            if (@count($HTTP_POST_VARS["notify_senders"]) > 0) {
                $recipients = Notification::notifyEmailConvertedIntoIssue($prj_id, $new_issue_id, $HTTP_POST_VARS["notify_senders"], $customer_id);
            } else {
                $recipients = array();
            }
            // need to process any custom fields ?
            if (@count($HTTP_POST_VARS["custom_fields"]) > 0) {
                foreach ($HTTP_POST_VARS["custom_fields"] as $fld_id => $value) {
                    Custom_Field::associateIssue($new_issue_id, $fld_id, $value);
                }
            }
            // also send a special confirmation email to the customer contact
            if ((@$HTTP_POST_VARS['notify_customer'] == 'yes') && (!empty($HTTP_POST_VARS['contact']))) {
                // also need to pass the list of sender emails already notified,
                // so we can avoid notifying the same person again
                $contact_email = User::getEmailByContactID($HTTP_POST_VARS['contact']);
                if (@!in_array($contact_email, $recipients)) {
                    Customer::notifyCustomerIssue($prj_id, $new_issue_id, $HTTP_POST_VARS['contact']);
                }
            }

            Workflow::handleNewIssue($prj_id, $new_issue_id, $has_TAM, $has_RR);

            // also notify any users that want to receive emails anytime a new issue is created
            Notification::notifyNewIssue($prj_id, $new_issue_id);

            return $new_issue_id;
        }
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
        $profile = Search_Profile::getProfile(Auth::getUserID(), Auth::getCurrentProject(), 'issue');

        if (isset($HTTP_GET_VARS[$name])) {
            return $HTTP_GET_VARS[$name];
        } elseif (isset($HTTP_POST_VARS[$name])) {
            return $HTTP_POST_VARS[$name];
        } elseif (isset($profile[$name])) {
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
        $sort_by = Issue::getParam('sort_by');
        $sort_order = Issue::getParam('sort_order');
        $rows = Issue::getParam('rows');
        $hide_closed = Issue::getParam('hide_closed');
        if ($hide_closed === '') {
            $hide_closed = 1;
        }
        $search_type = Issue::getParam('search_type');
        if (empty($search_type)) {
            $search_type = 'all_text';
        }
        $custom_field = Issue::getParam('custom_field');
        if (is_string($custom_field)) {
            $custom_field = unserialize(urldecode($custom_field));
        }
        $cookie = array(
            'rows'           => $rows ? $rows : APP_DEFAULT_PAGER_SIZE,
            'pagerRow'       => Issue::getParam('pagerRow'),
            'hide_closed'    => $hide_closed,
            "sort_by"        => $sort_by ? $sort_by : "pri_rank",
            "sort_order"     => $sort_order ? $sort_order : "ASC",
            // quick filter form
            'keywords'       => Issue::getParam('keywords'),
            'search_type'    => $search_type,
            'users'          => Issue::getParam('users'),
            'status'         => Issue::getParam('status'),
            'priority'       => Issue::getParam('priority'),
            'category'       => Issue::getParam('category'),
            'customer_email' => Issue::getParam('customer_email'),
            // advanced search form
            'show_authorized_issues'        => Issue::getParam('show_authorized_issues'),
            'show_notification_list_issues' => Issue::getParam('show_notification_list_issues'),
            'reporter'       => Issue::getParam('reporter'),
            // other fields
            'release'        => Issue::getParam('release'),
            // custom fields
            'custom_field'   => $custom_field
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
            if (@$field['filter_type'] == 'in_past') {
                @$cookie[$field_name] = array(
                    'filter_type'   =>  'in_past',
                    'time_period'   =>  $field['time_period']
                );
            } else {
                $end_field_name = $field_name . '_end';
                $end_field = Issue::getParam($end_field_name);
                @$cookie[$field_name] = array(
                    'past_hour'   => $field['past_hour'],
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
        }
        Search_Profile::save(Auth::getUserID(), Auth::getCurrentProject(), 'issue', $cookie);
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
            "pri_rank",
            "iss_id",
            "iss_customer_id",
            "prc_title",
            "sta_rank",
            "iss_created_date",
            "iss_summary",
            "last_action_date",
            "usr_full_name",
            "iss_expected_resolution_date"
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
     * Returns the list of action date fields appropriate for the
     * current user ID.
     *
     * @access  public
     * @return  array The list of action date fields
     */
    function getLastActionFields()
    {
        $last_action_fields = array(
            "iss_last_public_action_date"
        );
        if (Auth::getCurrentRole() > User::getRoleID('Customer')) {
            $last_action_fields[] = "iss_last_internal_action_date";
        }
        if (count($last_action_fields) > 1) {
            return "GREATEST(" . implode(', IFNULL(', $last_action_fields) . ", '0000-00-00')) AS last_action_date";
        } else {
            return $last_action_fields[0] . " AS last_action_date";
        }
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
        if (strtoupper($max) == "ALL") {
            $max = 9999999;
        }
        $start = $current_row * $max;
        // get the current user's role
        $usr_id = Auth::getUserID();
        $role_id = User::getRoleByUser($usr_id, $prj_id);

        // get any custom fields that should be displayed
        $custom_fields = Custom_Field::getFieldsToBeListed($prj_id);

        $stmt = "SELECT
                    iss_id,
                    iss_grp_id,
                    iss_prj_id,
                    iss_sta_id,
                    iss_customer_id,
                    iss_created_date,
                    iss_updated_date,
                    iss_last_response_date,
                    iss_closed_date,
                    iss_last_customer_action_date,
                    iss_usr_id,
                    iss_summary,
                    pri_title,
                    prc_title,
                    sta_title,
                    sta_color status_color,
                    sta_id,
                    iqu_status,
                    grp_name `group`,
                    pre_title,
                    iss_last_public_action_date,
                    iss_last_public_action_type,
                    iss_last_internal_action_date,
                    iss_last_internal_action_type,
                    " . Issue::getLastActionFields() . ",
                    IF(iss_last_internal_action_date > iss_last_public_action_date, 'internal', 'public') AS action_type,
                    iss_private,
                    usr_full_name,
                    iss_percent_complete,
                    iss_dev_time,
                    iss_expected_resolution_date
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user";
        // join custom fields if we are searching by custom fields
        if ((is_array($options['custom_field'])) && (count($options['custom_field']) > 0)) {
            foreach ($options['custom_field'] as $fld_id => $search_value) {
                if (empty($search_value)) {
                    continue;
                }
                $field = Custom_Field::getDetails($fld_id);
                if ($field['fld_type'] == 'multiple') {
                    $search_value = Misc::escapeInteger($search_value);
                    foreach ($search_value as $cfo_id) {
                        $stmt .= ",\n" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field as cf" . $fld_id . '_' . $cfo_id . "\n";
                    }
                } else {
                    $stmt .= ",\n" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field as cf" . $fld_id . "\n";
                }
            }
        }
        $stmt .= ")";
        if (!empty($options["users"])) {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 ON
                    isu_iss_id=iss_id";
        }
        if ((!empty($options["show_authorized_issues"])) || (($role_id == User::getRoleID("Reporter")) && (Project::getSegregateReporters($prj_id)))) {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                 ON
                    iur_iss_id=iss_id";
        }
        if (!empty($options["show_notification_list_issues"])) {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 ON
                    sub_iss_id=iss_id";
        }
        $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`
                 ON
                    iss_grp_id=grp_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category
                 ON
                    iss_prc_id=prc_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 ON
                    iss_pre_id = pre_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 ON
                    iss_pri_id=pri_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_quarantine
                 ON
                    iss_id=iqu_iss_id AND
                    (iqu_expiration > '" . Date_API::getCurrentDateGMT() . "' OR iqu_expiration IS NULL)
                 WHERE
                    iss_prj_id= " . Misc::escapeInteger($prj_id);
        $stmt .= Issue::buildWhereClause($options);
        $stmt .= "
                 GROUP BY
                    iss_id
                 ORDER BY
                    " . Misc::escapeString($options["sort_by"]) . " " . Misc::escapeString($options["sort_order"]) . ",
                    iss_id DESC";
        $total_rows = Pager::getTotalRows($stmt);
        $stmt .= "
                 LIMIT
                    " . Misc::escapeInteger($start) . ", " . Misc::escapeInteger($max);
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
                // need to get the customer titles for all of these issues...
                if (Customer::hasCustomerIntegration($prj_id)) {
                    Customer::getCustomerTitlesByIssues($prj_id, $res);
                }
                Issue::formatLastActionDates($res);
                Issue::getLastStatusChangeDates($prj_id, $res);
            } elseif ($current_row > 0) {
                // if there are no results, and the page is not the first page reset page to one and reload results
                Auth::redirect(APP_RELATIVE_URL . "list.php?pagerRow=0&rows=$max");
            }
            $groups = Group::getAssocList($prj_id);
            $categories = Category::getAssocList($prj_id);
            $column_headings = Issue::getColumnHeadings($prj_id);
            if (count($custom_fields) > 0) {
                $column_headings = array_merge($column_headings,$custom_fields);
            }
            $csv[] = @implode("\t", $column_headings);
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["time_spent"] = Misc::getFormattedTime($res[$i]["time_spent"]);
                $res[$i]["iss_expected_resolution_date"] = Date_API::getSimpleDate($res[$i]["iss_expected_resolution_date"]);
                $fields = array(
                    $res[$i]['pri_title'],
                    $res[$i]['iss_id']
                );
                // hide the group column from the output if no
                // groups are available in the database
                if (count($groups) > 0) {
                    $fields[] = $res[$i]['group'];
                }
                $fields[] = $res[$i]['assigned_users'];
                $fields[] = $res[$i]['time_spent'];
                // hide the category column from the output if no
                // categories are available in the database
                if (count($categories) > 0) {
                    $fields[] = $res[$i]['prc_title'];
                }
                if (Customer::hasCustomerIntegration($prj_id)) {
                    $fields[] = @$res[$i]['customer_title'];
                    // check if current user is acustomer and has a per incident contract.
                    // if so, check if issue is redeemed.
                    if (User::getRoleByUser($usr_id, $prj_id) == User::getRoleID('Customer')) {
                        if ((Customer::hasPerIncidentContract($prj_id, Issue::getCustomerID($res[$i]['iss_id'])) &&
                                (Customer::isRedeemedIncident($prj_id, $res[$i]['iss_id'])))) {
                            $res[$i]['redeemed'] = true;
                        }
                    }
                }
                $fields[] = $res[$i]['sta_title'];
                $fields[] = $res[$i]["status_change_date"];
                $fields[] = $res[$i]["last_action_date"];
                $fields[] = $res[$i]['iss_summary'];

                if (count($custom_fields) > 0) {
                    $res[$i]['custom_field'] = array();
                    $custom_field_values = Custom_Field::getListByIssue($prj_id, $res[$i]['iss_id']);
                    foreach ($custom_field_values as $this_field) {
                        if (!empty($custom_fields[$this_field['fld_id']])) {
                            $res[$i]['custom_field'][$this_field['fld_id']] = $this_field['icf_value'];
                            $fields[] = $this_field['icf_value'];
                        }
                    }
                }

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
                    "last_page"     => $last_page,
                    "custom_fields" => $custom_fields
                ),
                "csv" => @implode("\n", $csv)
            );
        }
    }


    /**
     * Processes a result set to format the "Last Action Date" column.
     *
     * @access  public
     * @param   array $result The result set
     */
    function formatLastActionDates(&$result)
    {
        for ($i = 0; $i < count($result); $i++) {
            if (($result[$i]['action_type'] == "internal") &&
                    (Auth::getCurrentRole() > User::getRoleID('Customer'))) {
                $label = $result[$i]["iss_last_internal_action_type"];
                $last_date = $result[$i]["iss_last_internal_action_date"];
            } else {
                $label = $result[$i]["iss_last_public_action_type"];
                $last_date = $result[$i]["iss_last_public_action_date"];
            }
            $date = new Date($last_date);
            $current = new Date(Date_API::getCurrentDateGMT());
            $result[$i]['last_action_date'] = sprintf("%s: %s ago", ucwords($label),
                    Date_API::getFormattedDateDiff($current->getDate(DATE_FORMAT_UNIXTIME), $date->getDate(DATE_FORMAT_UNIXTIME)));
        }
    }


    /**
     * Retrieves the last status change date for the given issue.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   array $result The associative array of data
     * @see     Issue::getListing()
     */
    function getLastStatusChangeDates($prj_id, &$result)
    {
        $ids = array();
        for ($i = 0; $i < count($result); $i++) {
            $ids[] = $result[$i]["iss_sta_id"];
        }
        if (count($ids) == 0) {
            return false;
        }
        $customizations = Status::getProjectStatusCustomization($prj_id, $ids);
        for ($i = 0; $i < count($result); $i++) {
            if (empty($result[$i]['iss_sta_id'])) {
                $result[$i]['status_change_date'] = '';
            } else {
                list($label, $date_field_name) = @$customizations[$result[$i]['iss_sta_id']];
                if ((empty($label)) || (empty($date_field_name))) {
                    $result[$i]['status_change_date'] = '';
                    continue;
                }
                $current = new Date(Date_API::getCurrentDateGMT());
                $desc = "$label: %s ago";
                $target_date = $result[$i][$date_field_name];
                if (empty($target_date)) {
                    $result[$i]['status_change_date'] = '';
                    continue;
                }
                $date = new Date($target_date);
                $result[$i]['status_change_date'] = sprintf($desc, Date_API::getFormattedDateDiff($current->getDate(DATE_FORMAT_UNIXTIME), $date->getDate(DATE_FORMAT_UNIXTIME)));
            }
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
        $prj_id = Auth::getCurrentProject();
        $role_id = User::getRoleByUser($usr_id, $prj_id);

        $stmt = ' AND iss_usr_id = usr_id';
        if (User::getRole($role_id) == "Customer") {
            $stmt .= " AND iss_customer_id=" . User::getCustomerID($usr_id);
        } elseif (($role_id == User::getRoleID("Reporter")) && (Project::getSegregateReporters($prj_id))) {
            $stmt .= " AND (
                        iss_usr_id = $usr_id OR
                        iur_usr_id = $usr_id
                        )";
        }

        if (!empty($options["users"])) {
            $stmt .= " AND (\n";
            if (stristr($options["users"], "grp") !== false) {
                $chunks = explode(":", $options["users"]);
                $stmt .= 'iss_grp_id = ' . Misc::escapeInteger($chunks[1]);
            } else {
                if ($options['users'] == '-1') {
                    $stmt .= 'isu_usr_id IS NULL';
                } elseif ($options['users'] == '-2') {
                    $stmt .= 'isu_usr_id IS NULL OR isu_usr_id=' . $usr_id;
                } elseif ($options['users'] == '-3') {
                    $stmt .= 'isu_usr_id = ' . $usr_id . ' OR iss_grp_id = ' . User::getGroupID($usr_id);
                } elseif ($options['users'] == '-4') {
                    $stmt .= 'isu_usr_id IS NULL OR isu_usr_id = ' . $usr_id . ' OR iss_grp_id = ' . User::getGroupID($usr_id);
                } else {
                    $stmt .= 'isu_usr_id =' . Misc::escapeInteger($options["users"]);
                }
            }
            $stmt .= ')';
        }
        if (!empty($options["reporter"])) {
            $stmt .= " AND iss_usr_id = " . Misc::escapeInteger($options["reporter"]);
        }
        if (!empty($options["show_authorized_issues"])) {
            $stmt .= " AND (iur_usr_id=$usr_id)";
        }
        if (!empty($options["show_notification_list_issues"])) {
            $stmt .= " AND (sub_usr_id=$usr_id)";
        }
        if (!empty($options["keywords"])) {
            $stmt .= " AND (\n";
            if (($options['search_type'] == 'all_text') && (APP_ENABLE_FULLTEXT)) {
                $stmt .= "iss_id IN(" . join(', ', Issue::getFullTextIssues($options)) . ")";
            } elseif (($options['search_type'] == 'customer') && (Customer::hasCustomerIntegration($prj_id))) {
                // check if the user is trying to search by customer email
                $customer_ids = Customer::getCustomerIDsLikeEmail($prj_id, $options['keywords']);
                if (count($customer_ids) > 0) {
                    $stmt .= " iss_customer_id IN (" . implode(', ', $customer_ids) . ")";
                } else {
                    // no results, kill query
                    $stmt .= " iss_customer_id = -1";
                }
            } else {
                $stmt .= "(" . Misc::prepareBooleanSearch('iss_summary', $options["keywords"]);
                $stmt .= " OR " . Misc::prepareBooleanSearch('iss_description', $options["keywords"]) . ")";
            }
            $stmt .= "\n) ";
        }
        if (!empty($options["priority"])) {
            $stmt .= " AND iss_pri_id=" . Misc::escapeInteger($options["priority"]);
        }
        if (!empty($options["status"])) {
            $stmt .= " AND iss_sta_id=" . Misc::escapeInteger($options["status"]);
        }
        if (!empty($options["category"])) {
            $stmt .= " AND iss_prc_id=" . Misc::escapeInteger($options["category"]);
        }
        if (!empty($options["hide_closed"])) {
            $stmt .= " AND sta_is_closed=0";
        }
        if (!empty($options['release'])) {
            $stmt .= " AND iss_pre_id = " . Misc::escapeInteger($options['release']);
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
                        $stmt .= " AND iss_$field_name >= '" . Misc::escapeString($options[$field_name]['start']) . "'";
                        break;
                    case 'less':
                        $stmt .= " AND iss_$field_name <= '" . Misc::escapeString($options[$field_name]['start']) . "'";
                        break;
                    case 'between':
                        $stmt .= " AND iss_$field_name BETWEEN '" . Misc::escapeString($options[$field_name]['start']) . "' AND '" . Misc::escapeString($options[$field_name]['end']) . "'";
                        break;
                    case 'null':
                        $stmt .= " AND iss_$field_name IS NULL";
                        break;
                    case 'in_past':
                        if (strlen($options[$field_name]['time_period']) == 0) {
                            $options[$field_name]['time_period'] = 0;
                        }
                        $stmt .= " AND (UNIX_TIMESTAMP('" . Date_API::getCurrentDateGMT() . "') - UNIX_TIMESTAMP(iss_$field_name)) <= (" .
                            Misc::escapeInteger($options[$field_name]['time_period']) . "*3600)";
                        break;
                }
            }
        }
        // custom fields
        if ((is_array($options['custom_field'])) && (count($options['custom_field']) > 0)) {
            foreach ($options['custom_field'] as $fld_id => $search_value) {
                if (empty($search_value)) {
                    continue;
                }
                $field = Custom_Field::getDetails($fld_id);
                if ($field['fld_type'] == 'multiple') {
                    $search_value = Misc::escapeInteger($search_value);
                    foreach ($search_value as $cfo_id) {
                        $stmt .= " AND\n cf" . $fld_id . '_' . $cfo_id . ".icf_iss_id = iss_id";
                        $stmt .= " AND\n cf" . $fld_id . '_' . $cfo_id . ".icf_fld_id = $fld_id";
                        $stmt .= " AND\n cf" . $fld_id . '_' . $cfo_id . ".icf_value = $cfo_id";
                    }
                } else {
                    $stmt .= " AND\n (iss_id = cf" . $fld_id . ".icf_iss_id";
                    $stmt .= " AND\n cf" . $fld_id . ".icf_fld_id = $fld_id";
                    if (in_array($field['fld_type'], array('text', 'textarea'))) {
                        $stmt .= " AND cf" . $fld_id . ".icf_value LIKE '%" . Misc::escapeString($search_value) . "%'";
                    } elseif ($field['fld_type'] == 'combo') {
                        $stmt .= " AND cf" . $fld_id . ".icf_value IN(" . join(', ', Misc::escapeInteger($search_value)) . ")";
                    }
                    $stmt .= ')';
                }
            }
        }

        // clear cached full-text values if we are not searching fulltext anymore
        if ((APP_ENABLE_FULLTEXT) && (@$options['search_type'] != 'all_text')) {
            Session::set('fulltext_string', '');
            Session::set('fulltext_issues', '');
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
        $role_id = Auth::getCurrentRole();

        $stmt = "SELECT
                    iss_id,
                    " . Issue::getLastActionFields() . "
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user";
        // join custom fields if we are searching by custom fields
        if ((is_array($options['custom_field'])) && (count($options['custom_field']) > 0)) {
            foreach ($options['custom_field'] as $fld_id => $search_value) {
                if (empty($search_value)) {
                    continue;
                }
                $field = Custom_Field::getDetails($fld_id);
                if ($field['fld_type'] == 'multiple') {
                    $search_value = Misc::escapeInteger($search_value);
                    foreach ($search_value as $cfo_id) {
                        $stmt .= ",\n" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field as cf" . $fld_id . '_' . $cfo_id . "\n";
                    }
                } else {
                    $stmt .= ",\n" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field as cf" . $fld_id . "\n";
                }
            }
        }
        $stmt .= ")";
        if (!empty($options["users"])) {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 ON
                    isu_iss_id=iss_id";
        }
        if ((!empty($options["show_authorized_issues"])) || (($role_id == User::getRoleID("Reporter")) && (Project::getSegregateReporters(Auth::getCurrentProject())))) {
             $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                 ON
                    iur_iss_id=iss_id";
        }
        if (!empty($options["show_notification_list_issues"])) {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 ON
                    sub_iss_id=iss_id";
        }
        if (@$options['sort_by'] == 'prc_title') {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category
                 ON
                    iss_prc_id = prc_id";
        }
        $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 ON
                    iss_pri_id=pri_id
                 WHERE
                    iss_prj_id=" . Auth::getCurrentProject();
        $stmt .= Issue::buildWhereClause($options);
        $stmt .= "
                 GROUP BY
                    iss_id
                 ORDER BY
                    " . Misc::escapeString($options["sort_by"]) . " " . Misc::escapeString($options["sort_order"]) . ",
                    iss_id DESC";
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
                    isu_iss_id=" . Misc::escapeInteger($issue_id) . " AND
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
     * Method used to see if a user is assigned to an issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id An integer containg the ID of the user.
     * @return  boolean true if the user(s) are assigned to the issue.
     */
    function isAssignedToUser($issue_id, $usr_id)
    {
        $assigned_users = Issue::getAssignedUserIDs($issue_id);
        if (in_array($usr_id, $assigned_users)) {
            return true;
        } else {
            return false;
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
        if (count($ids) < 1) {
            return;
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
     * Method used to add the issue description to a list of issues.
     *
     * @access  public
     * @param   array $result The result set
     * @return  void
     */
    function getDescriptionByIssues(&$result)
    {
        if (count($result) == 0) {
            return;
        }

        $ids = array();
        for ($i = 0; $i < count($result); $i++) {
            $ids[] = $result[$i]["iss_id"];
        }
        $ids = implode(", ", $ids);

        $stmt = "SELECT
                    iss_id,
                    iss_description
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id in ($ids)";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            for ($i = 0; $i < count($result); $i++) {
                @$result[$i]['iss_description'] = $res[$result[$i]['iss_id']];
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
                    isu_iss_id=" . Misc::escapeInteger($issue_id) . " AND
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
     * @param   boolean $force_refresh If the cache should not be used.
     * @return  array The details for the specified issue
     */
    function getDetails($issue_id, $force_refresh = false)
    {
        global $HTTP_SERVER_VARS;
        static $returns;

        $issue_id = Misc::escapeInteger($issue_id);

        if (empty($issue_id)) {
            return '';
        }

        if ((!empty($returns[$issue_id])) && ($force_refresh != true)) {
            return $returns[$issue_id];
        }

        $stmt = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue.*,
                    prj_title,
                    prc_title,
                    pre_title,
                    pri_title,
                    sta_title,
                    sta_abbreviation,
                    sta_color status_color,
                    sta_is_closed
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                    )
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
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
                    iss_id=$issue_id AND
                    iss_prj_id=prj_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (empty($res)) {
                return "";
            } else {
                $created_date_ts = Date_API::getUnixTimestamp($res['iss_created_date'], Date_API::getDefaultTimezone());
                // get customer information, if any
                if ((!empty($res['iss_customer_id'])) && (Customer::hasCustomerIntegration($res['iss_prj_id']))) {
                    $res['customer_business_hours'] = Customer::getBusinessHours($res['iss_prj_id'], $res['iss_customer_id']);
                    $res['contact_local_time'] = Date_API::getFormattedDate(Date_API::getCurrentDateGMT(), $res['iss_contact_timezone']);
                    $res['customer_info'] = Customer::getDetails($res['iss_prj_id'], $res['iss_customer_id']);
                    $res['redeemed_incidents'] = Customer::getRedeemedIncidentDetails($res['iss_prj_id'], $res['iss_id']);
                    $max_first_response_time = Customer::getMaximumFirstResponseTime($res['iss_prj_id'], $res['iss_customer_id']);
                    $res['max_first_response_time'] = Misc::getFormattedTime($max_first_response_time / 60);
                    if (empty($res['iss_first_response_date'])) {
                        $first_response_deadline = $created_date_ts + $max_first_response_time;
                        if (Date_API::getCurrentUnixTimestampGMT() <= $first_response_deadline) {
                            $res['max_first_response_time_left'] = Date_API::getFormattedDateDiff($first_response_deadline, Date_API::getCurrentUnixTimestampGMT());
                        } else {
                            $res['overdue_first_response_time'] = Date_API::getFormattedDateDiff(Date_API::getCurrentUnixTimestampGMT(), $first_response_deadline);
                        }
                    }
                }
                $res['iss_original_description'] = $res["iss_description"];
                if (!strstr($HTTP_SERVER_VARS["PHP_SELF"], 'update.php')) {
                    $res["iss_description"] = nl2br(htmlspecialchars($res["iss_description"]));
                    $res["iss_resolution"] = Resolution::getTitle($res["iss_res_id"]);
                }
                $res["iss_impact_analysis"] = nl2br(htmlspecialchars($res["iss_impact_analysis"]));
                $res["iss_created_date"] = Date_API::getFormattedDate($res["iss_created_date"]);
                $res['iss_created_date_ts'] = $created_date_ts;
                $res["assignments"] = @implode(", ", array_values(Issue::getAssignedUsers($res["iss_id"])));
                list($res['authorized_names'], $res['authorized_repliers']) = Authorized_Replier::getAuthorizedRepliers($res["iss_id"]);
                $temp = Issue::getAssignedUsersStatus($res["iss_id"]);
                $res["has_inactive_users"] = 0;
                $res["assigned_users"] = array();
                $res["assigned_inactive_users"] = array();
                foreach ($temp as $usr_id => $usr_status) {
                    if (!User::isActiveStatus($usr_status)) {
                        $res["assigned_inactive_users"][] = $usr_id;
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
                $res["associated_issues_details"] = Issue::getAssociatedIssuesDetails($res["iss_id"]);
                $res["associated_issues"] = Issue::getAssociatedIssues($res["iss_id"]);
                $res["reporter"] = User::getFullName($res["iss_usr_id"]);
                if (empty($res["iss_updated_date"])) {
                    $res["iss_updated_date"] = 'not updated yet';
                } else {
                    $res["iss_updated_date"] = Date_API::getFormattedDate($res["iss_updated_date"]);
                }
                $res["estimated_formatted_time"] = Misc::getFormattedTime($res["iss_dev_time"]);
                if (Release::isAssignable($res["iss_pre_id"])) {
                    $release = Release::getDetails($res["iss_pre_id"]);
                    $res["pre_title"] = $release["pre_title"];
                    $res["pre_status"] = $release["pre_status"];
                }
                // need to return the list of issues that are duplicates of this one
                $res["duplicates"] = Issue::getDuplicateList($res["iss_id"]);
                $res["duplicates_details"] = Issue::getDuplicateDetailsList($res["iss_id"]);
                // also get the issue title of the duplicated issue
                if (!empty($res['iss_duplicated_iss_id'])) {
                    $res['duplicated_issue'] = Issue::getDuplicatedDetails($res['iss_duplicated_iss_id']);
                }

                // get group information
                if (!empty($res["iss_grp_id"])) {
                    $res["group"] = Group::getDetails($res["iss_grp_id"]);
                }

                // get quarantine issue
                $res["quarantine"] = Issue::getQuarantineInfo($res["iss_id"]);

                $returns[$issue_id] = $res;
                return $res;
            }
        }
    }


    /**
     * Method used to get some simple details about the given duplicated issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The duplicated issue details
     */
    function getDuplicatedDetails($issue_id)
    {
        $stmt = "SELECT
                    iss_summary title,
                    sta_title current_status,
                    sta_is_closed is_closed
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    iss_sta_id=sta_id AND
                    iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to bulk update a list of issues
     *
     * @access  public
     * @return  boolean
     */
    function bulkUpdate()
    {
        global $HTTP_POST_VARS;

        // check if user performing this chance has the proper role
        if (Auth::getCurrentRole() < User::getRoleID('Manager')) {
            return -1;
        }

        $items = Misc::escapeInteger($HTTP_POST_VARS['item']);
        $new_status_id = Misc::escapeInteger($_POST['status']);
        $new_release_id = Misc::escapeInteger($_POST['release']);
        $new_priority_id = Misc::escapeInteger($_POST['priority']);
        $new_category_id = Misc::escapeInteger($_POST['category']);

        for ($i = 0; $i < count($items); $i++) {
            if (!Issue::canAccess($items[$i], Auth::getUserID())) {
                continue;
            } elseif (Issue::getProjectID($HTTP_POST_VARS['item'][$i]) != Auth::getCurrentProject()) {
                // make sure issue is not in another project
                continue;
            }

            $updated_fields = array();

            // update assignment
            if (count(@$HTTP_POST_VARS['users']) > 0) {
                $users = Misc::escapeInteger($HTTP_POST_VARS['users']);
                // get who this issue is currently assigned too
                $stmt = "SELECT
                            isu_usr_id,
                            usr_full_name
                         FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                         WHERE
                            isu_usr_id = usr_id AND
                            isu_iss_id = " . $items[$i];
                $current_assignees = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
                if (PEAR::isError($current_assignees)) {
                    Error_Handler::logError(array($current_assignees->getMessage(), $current_assignees->getDebugInfo()), __FILE__, __LINE__);
                    return -1;
                }
                foreach ($current_assignees as $usr_id => $usr_name) {
                    if (!in_array($usr_id, $users)) {
                        Issue::deleteUserAssociation($items[$i], $usr_id, false);
                    }
                }
                $new_user_names = array();
                $new_assignees = array();
                foreach ($users as $usr_id) {
                    $new_user_names[$usr_id] = User::getFullName($usr_id);

                    // check if the issue is already assigned to this person
                    $stmt = "SELECT
                                COUNT(*) AS total
                             FROM
                                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                             WHERE
                                isu_iss_id=" . $items[$i] . " AND
                                isu_usr_id=" . $usr_id;
                    $total = $GLOBALS["db_api"]->dbh->getOne($stmt);
                    if ($total > 0) {
                        continue;
                    } else {
                        $new_assignees[] = $usr_id;
                        // add the assignment
                        Issue::addUserAssociation(Auth::getUserID(), $items[$i], $usr_id, false);
                        Notification::subscribeUser(Auth::getUserID(), $items[$i], $usr_id, Notification::getAllActions());
                        Workflow::handleAssignment(Auth::getCurrentProject(), $items[$i], Auth::getUserID());
                    }
                }
                Notification::notifyNewAssignment($new_assignees, $items[$i]);
                $updated_fields['Assignment'] = History::formatChanges(join(', ', $current_assignees), join(', ', $new_user_names));
            }

            // update status
            if (!empty($new_status_id)) {
                $old_status_id = Issue::getStatusID($items[$i]);
                $res = Issue::setStatus($items[$i], $new_status_id, false);
                if ($res == 1) {
                    $updated_fields['Status'] = History::formatChanges(Status::getStatusTitle($old_status_id), Status::getStatusTitle($new_status_id));
                }
            }

            // update release
            if (!empty($new_release_id)) {
                $old_release_id = Issue::getRelease($items[$i]);
                $res = Issue::setRelease($items[$i], $new_release_id);
                if ($res == 1) {
                    $updated_fields['Release'] = History::formatChanges(Release::getTitle($old_release_id), Release::getTitle($new_release_id));
                }
            }

            // update priority
            if (!empty($new_priority_id)) {
                $old_priority_id = Issue::getPriority($items[$i]);
                $res = Issue::setPriority($items[$i], $new_priority_id);
                if ($res == 1) {
                    $updated_fields['Priority'] = History::formatChanges(Priority::getTitle($old_priority_id), Priority::getTitle($new_priority_id));
                }
            }

            // update category
            if (!empty($new_category_id)) {
                $old_category_id = Issue::getCategory($items[$i]);
                $res = Issue::setCategory($items[$i], $new_category_id);
                if ($res == 1) {
                    $updated_fields['Category'] = History::formatChanges(Category::getTitle($old_category_id), Category::getTitle($new_category_id));
                }
            }

            if (count($updated_fields) > 0) {
                // log the changes
                $changes = '';
                $k = 0;
                foreach ($updated_fields as $key => $value) {
                    if ($k > 0) {
                        $changes .= "; ";
                    }
                    $changes .= "$key: $value";
                    $k++;
                }
                History::add($items[$i], Auth::getUserID(), History::getTypeID('issue_bulk_updated'), "Issue updated ($changes) by " . User::getFullName(Auth::getUserID()));
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
                    iss_last_internal_action_date='" . Date_API::getCurrentDateGMT() . "',
                    iss_last_internal_action_type='update',
                    iss_developer_est_time=" . Misc::escapeInteger($HTTP_POST_VARS["dev_time"]) . ",
                    iss_impact_analysis='" . Misc::escapeString($HTTP_POST_VARS["impact_analysis"]) . "'
                 WHERE
                    iss_id=" . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // add the impact analysis to the history of the issue
            $summary = 'Initial Impact Analysis for issue set by ' . User::getFullName(Auth::getUserID());
            History::add($issue_id, Auth::getUserID(), History::getTypeID('impact_analysis_added'), $summary);
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
                    iss_id DESC";
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
        $issues = Issue::getAssociatedIssuesDetails($issue_id);
        $associated = array();
        for ($i = 0; $i < count($issues); $i++) {
            $associated[] = $issues[$i]['associated_issue'];
        }
        return $associated;
    }


    /**
     * Method used to get the list of issues associated details to a
     * specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of associated issues
     */
    function getAssociatedIssuesDetails($issue_id)
    {
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = "SELECT
                    isa_associated_id associated_issue,
                    iss_summary associated_title,
                    sta_title current_status,
                    sta_is_closed is_closed
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_association,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    isa_associated_id=iss_id AND
                    iss_sta_id=sta_id AND
                    isa_issue_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            $returns[$issue_id] = $res;
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
                    iss_id=" . Misc::escapeInteger($issue_id) . " AND
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


    /**
     * Returns a simple list of issues that are currently set to some
     * form of quarantine. This is mainly used by the IRC interface.
     *
     * @access  public
     * @return  array List of quarantined issues
     */
    function getQuarantinedIssueList()
    {
        // XXX: would be nice to restrict the result list to only one project
        $stmt = "SELECT
                    iss_id,
                    iss_summary
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_quarantine
                 WHERE
                    iqu_iss_id=iss_id AND
                    iqu_expiration >= '" . Date_API::getCurrentDateGMT() . "' AND
                    iqu_expiration IS NOT NULL";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            Issue::getAssignedUsersByIssues($res);
            return $res;
        }
    }


    /**
     * Returns the status of a quarantine.
     *
     * @param   integer $issue_id The issue ID
     * @return  integer Indicates what the current state of quarantine is.
     */
    function getQuarantineInfo($issue_id)
    {
        $stmt = "SELECT
                    iqu_status,
                    iqu_expiration
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_quarantine
                 WHERE
                    iqu_iss_id = " . Misc::escapeInteger($issue_id) . " AND
                        (iqu_expiration > '" . Date_API::getCurrentDateGMT() . "' OR
                        iqu_expiration IS NULL)";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (!empty($res["iqu_expiration"])) {
                $expiration_ts = Date_API::getUnixTimestamp($res['iqu_expiration'], Date_API::getDefaultTimezone());
                $res["time_till_expiration"] = Date_API::getFormattedDateDiff($expiration_ts, Date_API::getCurrentUnixTimestampGMT());
            }
            return $res;
        }
    }


    /**
     * Sets the quarantine status. Optionally an expiration date can be set
     * to indicate when the quarantine expires. A status > 0 indicates that quarantine is active.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $status The quarantine status
     * @param   string  $expiration The expiration date of quarantine (default empty)
     */
    function setQuarantine($issue_id, $status, $expiration = '')
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $status = Misc::escapeInteger($status);

        // see if there is an existing record
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_quarantine
                 WHERE
                    iqu_iss_id = $issue_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        if ($res > 0) {
            // update
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_quarantine
                     SET
                        iqu_status = $status";
            if (!empty($expiration)) {
                $stmt .= ",\niqu_expiration = '" . Misc::escapeString($expiration) . "'";
            }
            $stmt .= "\nWHERE
                        iqu_iss_id = $issue_id";
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                // add history entry about this change taking place
                if ($status == 0) {
                    History::add($issue_id, Auth::getUserID(), History::getTypeID('issue_quarantine_removed'),
                            "Issue quarantine status cleared by " . User::getFullName(Auth::getUserID()));
                }
            }
        } else {
            // insert
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_quarantine
                     (
                        iqu_iss_id,
                        iqu_status";
            if (!empty($expiration)) {
                $stmt .= ",\niqu_expiration\n";
            }
            $stmt .= ") VALUES (
                        $issue_id,
                        $status";
            if (!empty($expiration)) {
                $stmt .= ",\n'" . Misc::escapeString($expiration) . "'\n";
            }
            $stmt .= ")";
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            }
        }
        return 1;
    }


    /**
     * Sets the group of the issue.
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @param   integer $group_id The ID of the group
     * @return  integer 1 if successful, -1 or -2 otherwise
     */
    function setGroup($issue_id, $group_id)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $group_id = Misc::escapeInteger($group_id);

        $current = Issue::getDetails($issue_id);
        if ($current["iss_grp_id"] == $group_id) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_grp_id = $group_id
                 WHERE
                    iss_id = $issue_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        $current_user = Auth::getUserID();
        if (empty($current_user)) {
            $current_user = APP_SYSTEM_USER_ID;
        }
        History::add($issue_id, $current_user, History::getTypeID('group_changed'),
                "Group changed (" . History::formatChanges(Group::getName($current["iss_grp_id"]), Group::getName($group_id)) . ") by " . User::getFullName($current_user));
        return 1;
    }


    /**
     * Returns the group ID associated with the given issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The associated group ID
     */
    function getGroupID($issue_id)
    {
        $stmt = "SELECT
                    iss_grp_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_id=" . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } else {
            return $res;
        }
    }


    /**
     * Returns an array of issues based on full text search results.
     *
     * @param   array $options An array of search options
     * @return  array An array of issue IDS
     */
    function getFullTextIssues($options)
    {
        // check if a list of issues for this full text search is already cached
        $fulltext_string = Session::get('fulltext_string');
        if ((!empty($fulltext_string)) && ($fulltext_string == $options['keywords'])) {
            return Session::get('fulltext_issues');
        }

        // no pre-existing list, generate them
        $stmt = "(SELECT
                    DISTINCT(iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                     MATCH(iss_summary, iss_description) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    DISTINCT(not_iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                     MATCH(not_note) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    DISTINCT(ttr_iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                     MATCH(ttr_summary) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    DISTINCT(phs_iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                     MATCH(phs_description) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                     DISTINCT(sup_iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                 WHERE
                     sup_id = seb_sup_id AND
                     MATCH(seb_body) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 )";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array(-1);
        } else {
            $stmt = "SELECT
                        DISTINCT(icf_iss_id)
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                    WHERE
                        MATCH (icf_value) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)";
            $custom_res = $GLOBALS["db_api"]->dbh->getCol($stmt);
            if (PEAR::isError($custom_res)) {
                Error_Handler::logError(array($custom_res->getMessage(), $custom_res->getDebugInfo()), __FILE__, __LINE__);
                return array(-1);
            }
            $issues = array_merge($res, $custom_res);
            // we kill the query results on purpose to flag that no
            // issues could be found with fulltext search
            if (count($issues) < 1) {
                $issues = array(-1);
            }
            Session::set('fulltext_string', $options['keywords']);
            Session::set('fulltext_issues', $issues);
            return $issues;
        }
    }


    /**
     * Method to determine if user can access a particular issue
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The ID of the user
     * @return  boolean If the user can access the issue
     */
    function canAccess($issue_id, $usr_id)
    {
        static $access;

        if (empty($issue_id)) {
            return true;
        }

        if (isset($access[$issue_id . "-" . $usr_id])) {
            return $access[$issue_id . "-" . $usr_id];
        }

        $details = Issue::getDetails($issue_id);
        if (empty($details)) {
            return true;
        }
        $usr_details = User::getDetails($usr_id);
        $usr_role = User::getRoleByUser($usr_id, $details['iss_prj_id']);
        $prj_id = Issue::getProjectID($issue_id);

        // check customer permissions
        if ((Customer::hasCustomerIntegration($details['iss_prj_id'])) && ($usr_role == User::getRoleID("Customer")) &&
                ($details['iss_customer_id'] != $usr_details['usr_customer_id'])) {
            $return = false;
        } elseif ($details['iss_private'] == 1) {
            // check if the issue is even private

            // check role, reporter, assigment and group
            if (User::getRoleByUser($usr_id, $details['iss_prj_id']) > User::getRoleID("Developer")) {
                $return = true;
            } elseif ($details['iss_usr_id'] == $usr_id) {
                $return = true;
            } elseif (Issue::isAssignedToUser($issue_id, $usr_id)) {
                $return = true;
            } elseif ((!empty($details['iss_grp_id'])) && (!empty($usr_details['usr_grp_id'])) &&
                        ($details['iss_grp_id'] == $usr_details['usr_grp_id'])) {
                $return = true;
            } else {
                $return = false;
            }
        } elseif ((Auth::getCurrentRole() == User::getRoleID("Reporter")) && (Project::getSegregateReporters($prj_id)) &&
                ($details['iss_usr_id'] != $usr_id) && (!Authorized_Replier::isUserAuthorizedReplier($issue_id, $usr_id))) {
            return false;
        } else {
            $return = true;
        }

        $access[$issue_id . "-" . $usr_id] = $return;
        return $return;
    }


    /**
     * Returns true if the specified issue is private, false otherwise
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     * @return  boolean If the issue is private or not
     */
    function isPrivate($issue_id)
    {
        static $returns;

        if (!isset($returns[$issue_id])) {
            $sql = "SELECT
                        iss_private
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    WHERE
                        iss_id=$issue_id";
            $res = $GLOBALS["db_api"]->dbh->getOne($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return true;
            } else {
                if ($res == 1) {
                    $returns[$issue_id] = true;
                } else {
                    $returns[$issue_id] = false;
                }
            }
        }
        return $returns[$issue_id];
    }


    /**
     * Clears closed information from an issues.
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     */
    function clearClosed($issue_id)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_closed_date = null,
                    iss_res_id = null
                 WHERE
                    iss_id=" . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
    }


    /**
     * Returns the message ID that should be used as the parent ID for all messages
     *
     * @access  public
     * @param   integer $issue_id The ID of the issue
     */
    function getRootMessageID($issue_id)
    {
        $sql = "SELECT
                    iss_root_message_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                WHERE
                    iss_id=" . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return $res;
        }
    }


    /**
     * Returns the issue ID of the issue with the specified root message ID, or false
     * @access  public
     * @param   string $msg_id The Message ID
     * @return  integer The ID of the issue
     */
    function getIssueByRootMessageID($msg_id)
    {
        static $returns;

        if (!empty($returns[$msg_id])) {
            return $returns[$msg_id];
        }
        $sql = "SELECT
                    iss_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                WHERE
                    iss_root_message_id = '" . Misc::escapeString($msg_id) . "'";
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }
        if (empty($res)) {
            $returns[$msg_id] = false;
        } else {
            $returns[$msg_id] =  $res;
        }
        return $returns[$msg_id];
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Issue Class');
}
?>
