<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

class Email_Account
{
    /**
     * Method used to get the options related to the auto creation of
     * new issues.
     *
     * @param   integer $ema_id The email account ID
     * @return  array The issue auto creation options
     */
    public static function getIssueAutoCreationOptions($ema_id)
    {
        $stmt = 'SELECT
                    ema_issue_auto_creation_options
                 FROM
                    {{%email_account}}
                 WHERE
                    ema_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($ema_id));
        } catch (DbException $e) {
            return '';
        }

        if (!is_string($res)) {
            $res = (string) $res;
        }

        return unserialize($res);
    }

    /**
     * Method used to update the issue auto creation related options.
     *
     * @param   integer $ema_id The email account ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function updateIssueAutoCreation($ema_id, $auto_creation, $options)
    {
        $stmt = 'UPDATE
                    {{%email_account}}
                 SET
                    ema_issue_auto_creation=?,
                    ema_issue_auto_creation_options=?
                 WHERE
                    ema_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, array($auto_creation, @serialize($options), $ema_id));
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the support email account associated with a given
     * support email message.
     *
     * @param   integer $sup_id The support email ID
     * @return  integer The email account ID
     */
    public static function getAccountByEmail($sup_id)
    {
        $stmt = 'SELECT
                    sup_ema_id
                 FROM
                    {{%support_email}}
                 WHERE
                    sup_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($sup_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the account ID for a given email account.
     *
     * @param   string $username The username for the specific email account
     * @param   string $hostname The hostname for the specific email account
     * @param   string $mailbox The mailbox for the specific email account
     * @return  integer The support email account ID
     */
    public static function getAccountID($username, $hostname, $mailbox)
    {
        $stmt = 'SELECT
                    ema_id
                 FROM
                    {{%email_account}}
                 WHERE
                    ema_username=? AND
                    ema_hostname=?';
        try {
            $params = array($username, $hostname);
            if ($mailbox !== null) {
                $stmt .= ' AND ema_folder=?';
                $params[] = $mailbox;
            }
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DbException $e) {
            return 0;
        }

        if ($res == null) {
            return 0;
        }

        return $res;
    }

    /**
     * Method used to get the project ID associated with a given email account.
     *
     * @param   integer $ema_id The support email account ID
     * @return  integer The project ID
     */
    public static function getProjectID($ema_id)
    {
        $details = self::getDetails($ema_id);

        return $details['ema_prj_id'];
    }

    /**
     * Method used to get the details of a given support email
     * account.
     *
     * @param   integer $ema_id The support email account ID
     * @return  array The account details
     */
    public static function getDetails($ema_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%email_account}}
                 WHERE
                    ema_id=?';

        // IMPORTANT: do not print out $emai_id without sanitizing, it may contain XSS
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($ema_id));
        } catch (DbException $e) {
            throw new RuntimeException('email account not found');
        }

        if (!$res) {
            throw new RuntimeException('email account not found');
        }

        $res['ema_issue_auto_creation_options'] = @unserialize($res['ema_issue_auto_creation_options']);

        return $res;
    }

    /**
     * Method used to remove all support email accounts associated
     * with a specified set of projects.
     *
     * @param   array $ids The list of projects
     * @return  boolean
     */
    public static function removeAccountByProjects($ids)
    {
        $id_list = DB_Helper::buildList($ids);
        $stmt = "SELECT
                    ema_id
                 FROM
                    {{%email_account}}
                 WHERE
                    ema_prj_id IN ($id_list)";
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $ids);
        } catch (DbException $e) {
            return false;
        }

        Support::removeEmailByAccounts($res);

        $stmt = "DELETE FROM
                    {{%email_account}}
                 WHERE
                    ema_prj_id IN ($id_list)";
        try {
            DB_Helper::getInstance()->query($stmt, $ids);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove the specified support email accounts.
     *
     * @return  boolean
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $stmt = 'DELETE FROM
                    {{%email_account}}
                 WHERE
                    ema_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        Support::removeEmailByAccounts($items);

        return true;
    }

    /**
     * Method used to add a new support email account.
     *
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function insert()
    {
        if (empty($_POST['get_only_new'])) {
            $_POST['get_only_new'] = 0;
        }
        if (empty($_POST['leave_copy'])) {
            $_POST['leave_copy'] = 0;
        }
        if (empty($_POST['use_routing'])) {
            $_POST['use_routing'] = 0;
        } elseif ($_POST['use_routing'] == 1) {
            // if an account will be used for routing, you can't leave the message on the server
            $_POST['leave_copy'] = 0;
        }
        $stmt = 'INSERT INTO
                    {{%email_account}}
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
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?
                 )';
        $params = array(
            $_POST['project'],
            $_POST['type'],
            $_POST['hostname'],
            $_POST['port'],
            @$_POST['folder'],
            $_POST['username'],
            $_POST['password'],
            $_POST['get_only_new'],
            $_POST['leave_copy'],
            $_POST['use_routing'],
        );

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to update a support email account details.
     *
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function update()
    {
        if (empty($_POST['get_only_new'])) {
            $_POST['get_only_new'] = 0;
        }
        if (empty($_POST['leave_copy'])) {
            $_POST['leave_copy'] = 0;
        }
        if (empty($_POST['use_routing'])) {
            $_POST['use_routing'] = 0;
        } elseif ($_POST['use_routing'] == 1) {
            // if an account will be used for routing, you can't leave the message on the server
            $_POST['leave_copy'] = 0;
        }
        $stmt = 'UPDATE
                    {{%email_account}}
                 SET
                    ema_prj_id=?,
                    ema_type=?,
                    ema_hostname=?,
                    ema_port=?,
                    ema_folder=?,
                    ema_username=?,
                    ema_password=?,
                    ema_get_only_new=?,
                    ema_leave_copy=?,
                    ema_use_routing=?
                 WHERE
                    ema_id=?';
        $params = array(
            $_POST['project'],
            $_POST['type'],
            $_POST['hostname'],
            $_POST['port'],
            @$_POST['folder'],
            $_POST['username'],
            $_POST['password'],
            $_POST['get_only_new'],
            $_POST['leave_copy'],
            $_POST['use_routing'],
            $_POST['id'],
        );

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the list of available support email
     * accounts in the system.
     *
     * @return  array The list of accounts
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%email_account}}
                 ORDER BY
                    ema_hostname';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['prj_title'] = Project::getName($row['ema_prj_id']);
        }

        return $res;
    }

    /**
     * Method used to get an associative array of the support email
     * accounts in the format of account ID => account title.
     *
     * @param   integer $projects An array of project IDs
     * @return  array The list of accounts
     */
    public static function getAssocList($projects, $include_project_title = false)
    {
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
                    {{%email_account}},
                    {{%project}}
                 WHERE
                    prj_id = ema_prj_id AND
                    ema_prj_id IN (" . DB_Helper::buildList($projects) . ')
                 ORDER BY
                    ema_title';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $projects);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the first support email account associated
     * with the current activated project.
     *
     * @param   integer $prj_id The ID of the project. If blank the currently project will be used.
     * @return  integer The email account ID
     */
    public static function getEmailAccount($prj_id = false)
    {
        if ($prj_id == false) {
            $prj_id = Auth::getCurrentProject();
        }
        $stmt = 'SELECT
                    ema_id
                 FROM
                    {{%email_account}}
                 WHERE
                    ema_prj_id=?
                 LIMIT
                    1 OFFSET 0';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the email account associated with the given
     * issue' project.
     *
     * @param   integer $issue_id The issue ID
     * @return  integer The email account ID
     */
    public function getEmailAccountByIssueID($issue_id)
    {
        $stmt = 'SELECT
                    ema_id
                 FROM
                    {{%email_account}},
                    {{%issue}}
                 WHERE
                    ema_prj_id=iss_prj_id AND
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($issue_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }
}
