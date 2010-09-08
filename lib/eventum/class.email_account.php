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


class Email_Account
{
    /**
     * Method used to get the options related to the auto creation of
     * new issues.
     *
     * @access  public
     * @param   integer $ema_id The email account ID
     * @return  array The issue auto creation options
     */
    function getIssueAutoCreationOptions($ema_id)
    {
        $stmt = "SELECT
                    ema_issue_auto_creation_options
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id=$ema_id";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (!is_string($res)) {
                $res = (string) $res;
            }
            return @unserialize($res);
        }
    }


    /**
     * Method used to update the issue auto creation related options.
     *
     * @access  public
     * @param   integer $ema_id The email account ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function updateIssueAutoCreation($ema_id, $auto_creation, $options)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 SET
                    ema_issue_auto_creation='" . Misc::escapeString($auto_creation) . "',
                    ema_issue_auto_creation_options='" . @serialize($options) . "'
                 WHERE
                    ema_id=" . Misc::escapeInteger($ema_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
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
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
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
                    ema_username='" . Misc::escapeString($username) . "' AND
                    ema_hostname='" . Misc::escapeString($hostname) . "' AND
                    ema_folder='" . Misc::escapeString($mailbox) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
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
     * Method used to get the project ID associated with a given email account.
     *
     * @access  public
     * @param   integer $ema_id The support email account ID
     * @return  integer The project ID
     */
    function getProjectID($ema_id)
    {
        $details = self::getDetails($ema_id);
        return $details['ema_prj_id'];
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
        $ema_id = Misc::escapeInteger($ema_id);
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id=$ema_id";
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res['ema_issue_auto_creation_options'] = @unserialize($res['ema_issue_auto_creation_options']);
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
        $items = @implode(", ", Misc::escapeInteger($ids));
        $stmt = "SELECT
                    ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_prj_id IN ($items)";
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Support::removeEmailByAccounts($res);
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                     WHERE
                        ema_prj_id IN ($items)";
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
     * Method used to remove the specified support email accounts.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Support::removeEmailByAccounts($_POST["items"]);
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
        if (empty($_POST["get_only_new"])) {
            $_POST["get_only_new"] = 0;
        }
        if (empty($_POST["leave_copy"])) {
            $_POST["leave_copy"] = 0;
        }
        if (empty($_POST["use_routing"])) {
            $_POST["use_routing"] = 0;
        } elseif ($_POST['use_routing'] == 1) {
            // if an account will be used for routing, you can't leave the message on the server
            $_POST['leave_copy'] = 0;
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
                    ema_leave_copy,
                    ema_use_routing
                 ) VALUES (
                    " . Misc::escapeInteger($_POST["project"]) . ",
                    '" . Misc::escapeString($_POST["type"]) . "',
                    '" . Misc::escapeString($_POST["hostname"]) . "',
                    '" . Misc::escapeString($_POST["port"]) . "',
                    '" . Misc::escapeString(@$_POST["folder"]) . "',
                    '" . Misc::escapeString($_POST["username"]) . "',
                    '" . Misc::escapeString($_POST["password"]) . "',
                    " . Misc::escapeInteger($_POST["get_only_new"]) . ",
                    " . Misc::escapeInteger($_POST["leave_copy"]) . ",
                    " . Misc::escapeInteger($_POST["use_routing"]) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
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
        if (empty($_POST["get_only_new"])) {
            $_POST["get_only_new"] = 0;
        }
        if (empty($_POST["leave_copy"])) {
            $_POST["leave_copy"] = 0;
        }
        if (empty($_POST["use_routing"])) {
            $_POST["use_routing"] = 0;
        } elseif ($_POST['use_routing'] == 1) {
            // if an account will be used for routing, you can't leave the message on the server
            $_POST['leave_copy'] = 0;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 SET
                    ema_prj_id=" . Misc::escapeInteger($_POST["project"]) . ",
                    ema_type='" . Misc::escapeString($_POST["type"]) . "',
                    ema_hostname='" . Misc::escapeString($_POST["hostname"]) . "',
                    ema_port='" . Misc::escapeString($_POST["port"]) . "',
                    ema_folder='" . Misc::escapeString(@$_POST["folder"]) . "',
                    ema_username='" . Misc::escapeString($_POST["username"]) . "',
                    ema_password='" . Misc::escapeString($_POST["password"]) . "',
                    ema_get_only_new=" . Misc::escapeInteger($_POST["get_only_new"]) . ",
                    ema_leave_copy=" . Misc::escapeInteger($_POST["leave_copy"]) . ",
                    ema_use_routing=" . Misc::escapeInteger($_POST["use_routing"]) . "
                 WHERE
                    ema_id=" . $_POST["id"];
        $res = DB_Helper::getInstance()->query($stmt);
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
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
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
     * @param   integer $projects An array of project IDs
     * @return  array The list of accounts
     */
    function getAssocList($projects, $include_project_title = false)
    {
        $projects = Misc::escapeInteger($projects);
        if (!is_array($projects)) {
            $projects = array($projects);
        }
        if ($include_project_title) {
            $title_sql = "CONCAT(prj_title, ': ', ema_username, '@', ema_hostname, ' ', ema_folder)";
        } else {
            $title_sql = "CONCAT(ema_username, '@', ema_hostname, ' ', ema_folder)";
        }
        $stmt = "SELECT
                    ema_id,
                    $title_sql AS ema_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id = ema_prj_id AND
                    ema_prj_id IN (" . join(',', $projects) . ")
                 ORDER BY
                    ema_title";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the first support email account associated
     * with the current activated project.
     *
     * @access  public
     * @param   integer $prj_id The ID of the project. If blank the currently project will be used.
     * @return  integer The email account ID
     */
    function getEmailAccount($prj_id = false)
    {
        if ($prj_id == false) {
            $prj_id = Auth::getCurrentProject();
        }
        $stmt = "SELECT
                    ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    ema_prj_id=" . Misc::escapeInteger($prj_id) . "
                 LIMIT
                    0, 1";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the email account associated with the given
     * issue' project.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The email account ID
     */
    function getEmailAccountByIssueID($issue_id)
    {
        $stmt = "SELECT
                    ema_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    ema_prj_id=iss_prj_id AND
                    iss_id=$issue_id";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }
}
