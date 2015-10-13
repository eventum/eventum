<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+


/**
 * Class to handle the business logic related to the administration
 * of projects in the system.
 */

class Project
{
    /**
     * Method used to get the outgoing email sender address associated with
     * a given project.
     *
     * @param   integer $prj_id The project ID
     * @return  array The outgoing sender information
     */
    public static function getOutgoingSenderAddress($prj_id)
    {
        $default = array(
            'name'  => '',
            'email' => '',
        );
        $stmt = 'SELECT
                    prj_outgoing_sender_name,
                    prj_outgoing_sender_email
                 FROM
                    {{%project}}
                 WHERE
                    prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($prj_id));
        } catch (DbException $e) {
            return $default;
        }

        if (!empty($res)) {
            return array(
                'name'  => $res['prj_outgoing_sender_name'],
                'email' => $res['prj_outgoing_sender_email'],
            );
        }

        return $default;
    }

    /**
     * Method used to get the initial status that should be set to a new issue
     * created and associated with a given project.
     *
     * @param   integer $prj_id The project ID
     * @return  integer The status ID
     */
    public static function getInitialStatus($prj_id)
    {
        $stmt = 'SELECT
                    prj_initial_sta_id
                 FROM
                    {{%project}}
                 WHERE
                    prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the options related to the anonymous posting
     * of new issues.
     *
     * @param   integer $prj_id The project ID
     * @return  array The anonymous posting options
     */
    public static function getAnonymousPostOptions($prj_id)
    {
        $stmt = 'SELECT
                    prj_anonymous_post_options
                 FROM
                    {{%project}}
                 WHERE
                    prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        return @unserialize($res);
    }

    /**
     * Method used to update the anonymous posting related options.
     *
     * @param   integer $prj_id The project ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function updateAnonymousPost($prj_id)
    {
        $stmt = 'UPDATE
                    {{%project}}
                 SET
                    prj_anonymous_post=?,
                    prj_anonymous_post_options=?
                 WHERE
                    prj_id=?';
        $params = array($_POST['anonymous_post'], @serialize($_POST['options']), $prj_id);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the list of projects that allow anonymous
     * posting of new issues.
     *
     * @return  array The list of projects
     */
    public static function getAnonymousList()
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    {{%project}}
                 WHERE
                    prj_anonymous_post='enabled'
                 ORDER BY
                    prj_title";
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to check whether a project exists or not.
     *
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    public static function exists($prj_id)
    {
        $stmt = 'SELECT
                    COUNT(*) AS total
                 FROM
                    {{%project}}
                 WHERE
                    prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($prj_id));
        } catch (DbException $e) {
            return false;
        }

        if ($res > 0) {
            return true;
        }

        return false;
    }

    /**
     * Method used to get the project ID of the given project title.
     *
     * @param   string $prj_title The project title
     * @return  integer The project ID
     */
    public static function getID($prj_title)
    {
        $stmt = 'SELECT
                    prj_id
                 FROM
                    {{%project}}
                 WHERE
                    prj_title=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($prj_title));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the title of a given project ID.
     *
     * @param   integer $prj_id The project ID
     * @return  string The project title
     */
    public static function getName($prj_id)
    {
        static $returns;

        if (!empty($returns[$prj_id])) {
            return $returns[$prj_id];
        }

        $stmt = 'SELECT
                    prj_title
                 FROM
                    {{%project}}
                 WHERE
                    prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        $returns[$prj_id] = $res;

        return $res;
    }

    /**
     * Method used to get if reporters should be segregated for a project ID
     *
     * @param   integer $prj_id The project ID
     * @return  boolean If reporters should be segregated
     */
    public static function getSegregateReporters($prj_id)
    {
        static $returns;

        if (!empty($returns[$prj_id])) {
            return $returns[$prj_id];
        }

        $stmt = 'SELECT
                    prj_segregate_reporter
                 FROM
                    {{%project}}
                 WHERE
                    prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($prj_id));
        } catch (DbException $e) {
            // FIXME: why return true?
            return true;
        }

        if ($res == 1) {
            $res = true;
        } else {
            $res = false;
        }

        $returns[$prj_id] = $res;

        return $res;
    }

    /**
     * Method used to get the details for a given project ID.
     *
     * @param   integer $prj_id The project ID
     * @return  array The project details
     */
    public static function getDetails($prj_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%project}}
                 WHERE
                    prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($prj_id));
        } catch (DbException $e) {
        }

        if (empty($res)) {
            return array();
        }

        $res['prj_assigned_users'] = self::getUserColList($res['prj_id']);
        $res['assigned_statuses'] = array_keys(Status::getAssocStatusList($res['prj_id']));

        return $res;
    }

    /**
     * Method used to remove a given set of projects from the system.
     *
     * @return  boolean
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $stmt = 'DELETE FROM
                    {{%project}}
                 WHERE
                    prj_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return -1;
        }

        self::removeUserByProjects($items);
        Category::removeByProjects($items);
        Release::removeByProjects($items);
        Filter::removeByProjects($items);
        Email_Account::removeAccountByProjects($items);
        Issue::removeByProjects($items);
        Custom_Field::removeByProjects($items);

        $statuses = array_keys(Status::getAssocStatusList($items));
        foreach ($items as $prj_id) {
            Status::removeProjectAssociations($statuses, $prj_id);
        }
        Group::disassociateProjects($items);

        return 1;
    }

    /**
     * Method used to remove all project/user associations for a given
     * set of projects.
     *
     * @param   array $ids The project IDs
     * @param   array $users_to_not_remove Users that should not be removed
     * @return  boolean
     */
    public static function removeUserByProjects($ids, $users_to_not_remove = null)
    {
        $stmt = 'DELETE FROM
                    {{%project_user}}
                 WHERE
                    pru_prj_id IN (' . DB_Helper::buildList($ids) . ')';
        $params = $ids;
        if ($users_to_not_remove) {
            $stmt .= " AND\n pru_usr_id NOT IN (" . DB_Helper::buildList($users_to_not_remove) . ')';
            $params = array_merge($params, $users_to_not_remove);
        }

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update the details of the project information.
     *
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function update()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }

        $stmt = 'UPDATE
                    {{%project}}
                 SET
                    prj_title=?,
                    prj_status=?,
                    prj_lead_usr_id=?,
                    prj_initial_sta_id=?,
                    prj_outgoing_sender_name=?,
                    prj_outgoing_sender_email=?,
                    prj_mail_aliases=?,
                    prj_remote_invocation=?,
                    prj_segregate_reporter=?,
                    prj_customer_backend=?,
                    prj_workflow_backend=?
                 WHERE
                    prj_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, array(
                $_POST['title'],
                $_POST['status'],
                $_POST['lead_usr_id'],
                $_POST['initial_status'],
                $_POST['outgoing_sender_name'],
                $_POST['outgoing_sender_email'],
                $_POST['mail_aliases'],
                $_POST['remote_invocation'],
                $_POST['segregate_reporter'],
                $_POST['customer_backend'],
                $_POST['workflow_backend'],
                $_POST['id'],
            ));
        } catch (DbException $e) {
            return -1;
        }

        self::removeUserByProjects(array($_POST['id']), $_POST['users']);
        foreach ($_POST['users'] as $user) {
            if ($user == $_POST['lead_usr_id']) {
                self::associateUser($_POST['id'], $user, User::ROLE_MANAGER);
            } elseif (User::getRoleByUser($user, $_POST['id']) == '') {
                // users who are now being associated with this project should be set to 'Standard User'
                self::associateUser($_POST['id'], $user, User::ROLE_USER);
            }
        }

        $statuses = array_keys(Status::getAssocStatusList($_POST['id']));
        if (count($statuses) > 0) {
            Status::removeProjectAssociations($statuses, $_POST['id']);
        }
        foreach ($_POST['statuses'] as $sta_id) {
            Status::addProjectAssociation($sta_id, $_POST['id']);
        }

        return 1;
    }

    /**
     * Method used to associate an user to a project. If the user association already exists
     * no change will be made.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $usr_id The user ID
     * @param   integer $role The role of the user
     * @return  boolean
     */
    public static function associateUser($prj_id, $usr_id, $role)
    {
        // see if association already exists
        $sql = 'SELECT
                    pru_id
                FROM
                    {{%project_user}}
                WHERE
                    pru_prj_id = ? AND
                    pru_usr_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($prj_id, $usr_id));
        } catch (DbException $e) {
            return false;
        }

        if (empty($res)) {
            $stmt = 'INSERT INTO
                        {{%project_user}}
                     (
                        pru_usr_id,
                        pru_prj_id,
                        pru_role
                     ) VALUES (
                        ?, ?, ?
                     )';
            try {
                DB_Helper::getInstance()->query($stmt, array($usr_id, $prj_id, $role));
            } catch (DbException $e) {
                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * Method used to add a new project to the system.
     *
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    public static function insert()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'INSERT INTO
                    {{%project}}
                 (
                    prj_created_date,
                    prj_title,
                    prj_status,
                    prj_lead_usr_id,
                    prj_initial_sta_id,
                    prj_outgoing_sender_name,
                    prj_outgoing_sender_email,
                    prj_mail_aliases,
                    prj_remote_invocation,
                    prj_customer_backend,
                    prj_workflow_backend
                 ) VALUES (
                     ?, ?, ?, ?, ?,
                     ?, ?, ?, ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, array(
                Date_Helper::getCurrentDateGMT(),
                $_POST['title'],
                $_POST['status'],
                $_POST['lead_usr_id'],
                $_POST['initial_status'],
                $_POST['outgoing_sender_name'],
                $_POST['outgoing_sender_email'],
                $_POST['mail_aliases'],
                $_POST['remote_invocation'],
                $_POST['customer_backend'],
                $_POST['workflow_backend'],
            ));
        } catch (DbException $e) {
            return -1;
        }

        $new_prj_id = DB_Helper::get_last_insert_id();
        foreach ($_POST['users'] as $user) {
            if ($user == $_POST['lead_usr_id']) {
                $role_id = User::ROLE_MANAGER;
            } else {
                $role_id = User::ROLE_USER;
            }
            self::associateUser($new_prj_id, $user, $role_id);
        }
        foreach ($_POST['statuses'] as $sta_id) {
            Status::addProjectAssociation($sta_id, $new_prj_id);
        }
        Display_Column::setupNewProject($new_prj_id);

        // insert default timetracking categories
        Time_Tracking::addProjectDefaults($new_prj_id);

        return 1;
    }

    /**
     * Method used to get the list of projects available in the
     * system.
     *
     * @return  array The list of projects
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    prj_id,
                    prj_title,
                    prj_status,
                    usr_full_name
                 FROM
                    {{%project}},
                    {{%user}}
                 WHERE
                    prj_lead_usr_id=usr_id
                 ORDER BY
                    prj_title';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get an associative array of project ID and title
     * of all projects available in the system to a given user ID.
     *
     * @param   integer $usr_id The user ID
     * @param   boolean $force_refresh If the cache should not be used.
     * @param   boolean $include_extra If extra data should be included.
     * @return  array The list of projects
     */
    public static function getAssocList($usr_id, $force_refresh = false, $include_extra = false)
    {
        static $returns;

        if (!empty($returns[$usr_id][$include_extra]) && $force_refresh != true) {
            return $returns[$usr_id][$include_extra];
        }

        $stmt = 'SELECT
                    prj_id,
                    prj_title';
        if ($include_extra) {
            $stmt .= ',
                    pru_role,
                    prj_status as status';
        }
        $stmt .= '
                 FROM
                    {{%project}},
                    {{%project_user}}
                 WHERE
                    prj_id=pru_prj_id AND
                    pru_usr_id=? AND
                    (
                        prj_status <> ? OR
                        pru_role >= ?
                    )
                 ORDER BY
                    prj_title';
        try {
            $params = array(
                $usr_id,
                'archived',
                User::ROLE_MANAGER,
            );
            if ($include_extra) {
                $res = DB_Helper::getInstance()->fetchAssoc($stmt, $params, DB_FETCHMODE_ASSOC);
            } else {
                $res = DB_Helper::getInstance()->getPair($stmt, $params);
            }
        } catch (DbException $e) {
            return '';
        }

        if ($include_extra) {
            foreach ($res as $prj_id => $data) {
                $res[$prj_id]['role'] = User::getRole($data['pru_role']);
            }
        }
        $returns[$usr_id][$include_extra] = $res;

        return $res;
    }

    /**
     * Method used to get the list of users associated with a given project.
     *
     * @param   integer $prj_id The project ID
     * @param   string $status The desired user status
     * @param   integer $role The role ID of the user
     * @return  array The list of users
     */
    public static function getUserAssocList($prj_id, $status = null, $role = null)
    {
        $stmt = 'SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    {{%user}},
                    {{%project_user}}
                 WHERE
                    pru_prj_id=? AND
                    pru_usr_id=usr_id AND
                    usr_id != ?';
        $params = array($prj_id, APP_SYSTEM_USER_ID);
        if ($status != null) {
            $stmt .= " AND usr_status='active' ";
        }
        if ($role != null) {
            $stmt .= ' AND pru_role > ?';
            $params[] = $role;
        }
        $stmt .= '
                 ORDER BY
                    usr_full_name ASC';
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, $params);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get a list of user IDs associated with a given
     * project.
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of user IDs
     */
    public static function getUserColList($prj_id)
    {
        $stmt = 'SELECT
                    usr_id
                 FROM
                    {{%user}},
                    {{%project_user}}
                 WHERE
                    pru_prj_id=? AND
                    pru_usr_id=usr_id
                 ORDER BY
                    usr_full_name ASC';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get an associative array of project ID and title
     * of all projects that exist in the system.
     *
     * @param   boolean $include_no_customer_association Whether to include in the results projects with customer integration or not
     * @return  array List of projects
     */
    public static function getAll($include_no_customer_association = true)
    {
        $stmt = 'SELECT
                    prj_id,
                    prj_title
                 FROM
                    {{%project}}';
        if (!$include_no_customer_association) {
            $stmt .= " WHERE prj_customer_backend <> '' AND prj_customer_backend IS NOT NULL ";
        }
        $stmt .= '
                 ORDER BY
                    prj_title';
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get a list of emails that are associated with a given
     * project and issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @return  array List of emails
     */
    public static function getAddressBookEmails($prj_id, $issue_id)
    {
        $list = self::getAddressBook($prj_id, $issue_id);
        $emails = array();
        foreach ($list as $address => $name) {
            $emails[] = Mail_Helper::getEmailAddress($address);
        }

        return $emails;
    }

    /**
     * Method used to get a list of names and emails that are
     * associated with a given project and issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @return  array List of names and emails
     */
    public static function getAddressBook($prj_id, $issue_id = false)
    {
        static $returns;

        $key = serialize(array($prj_id, $issue_id));
        if (!empty($returns[$key])) {
            return $returns[$key];
        }

        $res = self::getAddressBookAssocList($prj_id, $issue_id);
        if (empty($res)) {
            return '';
        } else {
            $temp = array();
            foreach ($res as $name => $email) {
                $temp["$name <$email>"] = $name;
            }
            $returns[$key] = $temp;

            return $temp;
        }
    }

    /**
     * Method used to get an associative array of names and emails
     * that are associated with a given project and issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @return  array List of names and emails
     */
    public static function getAddressBookAssocList($prj_id, $issue_id = false)
    {
        if ($issue_id) {
            $customer_id = Issue::getCustomerID($issue_id);
        }

        $stmt = "SELECT
                    usr_full_name,
                    usr_email
                 FROM
                    {{%user}},
                    {{%project_user}}
                 WHERE
                    pru_prj_id=? AND
                    pru_usr_id=usr_id AND
                    usr_status='active' AND
                    usr_id <> ?";
        $params = array($prj_id, APP_SYSTEM_USER_ID);
        if (!empty($customer_id)) {
            $stmt .= ' AND (usr_customer_id IS NULL OR usr_customer_id IN (0, ?)) ';
            $params[] = $customer_id;
        } else {
            $stmt .= ' AND (usr_customer_id IS NULL OR usr_customer_id=0) ';
        }
        $stmt .= '
                 ORDER BY
                    usr_customer_id DESC,
                    usr_full_name ASC';
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, $params);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of projects that allow remote
     * invocation of issues.
     *
     * @return  array The list of projects
     */
    public static function getRemoteAssocList()
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    {{%project}}
                 WHERE
                    prj_remote_invocation='enabled'
                 ORDER BY
                    prj_title";
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of projects assigned to a given user that
     * allow remote invocation of issues.
     *
     * @param   integer $usr_id The user ID
     * @param   boolean $only_customer_projects Whether to only include projects with customer integration or not
     * @return  array The list of projects
     */
    public static function getRemoteAssocListByUser($usr_id, $only_customer_projects = false)
    {
        static $returns;

        if ((!$only_customer_projects) && (!empty($returns[$usr_id]))) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    {{%project}},
                    {{%project_user}}
                 WHERE
                    prj_id=pru_prj_id AND
                    pru_usr_id=? AND
                    pru_role > ? AND
                    prj_remote_invocation='enabled'";
        if ($only_customer_projects) {
            $stmt .= " AND prj_customer_backend <> '' AND prj_customer_backend IS NOT NULL ";
        }
        $stmt .= '
                 ORDER BY
                    prj_title';
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, array($usr_id, User::ROLE_CUSTOMER));
        } catch (DbException $e) {
            return '';
        }

        // don't cache the results when the optional argument is used to avoid getting bogus results
        if (!$only_customer_projects) {
            $returns[$usr_id] = $res;
        }

        return $res;
    }

    /**
     * Method used to get the list of users associated with a given project.
     *
     * @param   integer $prj_id The project ID
     * @param   string $status The desired user status
     * @return  array The list of users
     */
    public static function getUserEmailAssocList($prj_id, $status = null, $role = null)
    {
        static $returns;

        if (!empty($returns[$prj_id])) {
            return $returns[$prj_id];
        }

        $stmt = 'SELECT
                    usr_id,
                    usr_email
                 FROM
                    {{%user}},
                    {{%project_user}}
                 WHERE
                    pru_prj_id=? AND
                    pru_usr_id=usr_id';
        $params = array($prj_id);
        if ($status) {
            $stmt .= " AND usr_status='active' ";
        }
        if ($role) {
            $stmt .= ' AND pru_role > ?';
            $params[] = $role;
        }
        $stmt .= '
                 ORDER BY
                    usr_email ASC';
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, $params);
        } catch (DbException $e) {
            return '';
        }

        $returns[$prj_id] = $res;

        return $res;
    }

    /**
     * Method used to get the list of users associated with a given project.
     *
     * @param   integer $prj_id The project ID
     * @param   string $status The desired user status
     * @return  array The list of users
     */
    public static function getReporters($prj_id)
    {
        $stmt = 'SELECT
                    DISTINCT usr_id,
                    usr_full_name
                 FROM
                    {{%user}},
                    {{%project_user}},
                    {{%issue}}
                 WHERE
                    pru_prj_id = ? AND
                    iss_prj_id = ? AND
                    pru_usr_id = usr_id AND
                    usr_id = iss_usr_id
                 ORDER BY
                    usr_full_name ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($prj_id, $prj_id));
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Sets the minimum role needed to view a specific field on the issue creation form.
     *
     * @param   integer $prj_id The project ID.
     * @param   array $settings An array of fields and role is required to view them.
     * @return  integer 1 if the update worked, -1 otherwise.
     */
    public static function updateFieldDisplaySettings($prj_id, $settings)
    {
        // delete current settings
        $stmt = 'DELETE FROM
                    {{%project_field_display}}
                 WHERE
                    pfd_prj_id = ?';
        try {
            DB_Helper::getInstance()->query($stmt, array($prj_id));
        } catch (DbException $e) {
            return -1;
        }

        // insert new values
        foreach ($settings as $field => $details) {
            $stmt = 'INSERT INTO
                        {{%project_field_display}}
                     (
                        pfd_prj_id,
                        pfd_field,
                        pfd_min_role,
                        pfd_required
                     ) VALUES (
                        ?, ?, ?, ?
                     )';
            try {
                DB_Helper::getInstance()->query($stmt, array($prj_id, $field, $details['min_role'],
                    (isset($details['required']) ? $details['required'] : 0)));
            } catch (DbException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Returns display settings for a specific project.
     *
     * @param   integer $prj_id The project ID
     * @return  array An associative array of minimum role required to access a field.
     */
    public static function getFieldDisplaySettings($prj_id)
    {
        $stmt = 'SELECT
                    pfd_field,
                    pfd_min_role as min_role,
                    pfd_required as required
                 FROM
                    {{%project_field_display}}
                 WHERE
                    pfd_prj_id = ?';
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, array($prj_id), DbInterface::DB_FETCHMODE_ASSOC);
        } catch (DbException $e) {
            return -1;
        }

        $fields = self::getDisplayFields();
        foreach ($fields as $field_name => $field_info) {
            if (!isset($res[$field_name])) {
                $res[$field_name] = array(
                    'required'  =>  $field_info['required'],
                    'min_role'  =>  0,
                );
            }
        }

        return $res;
    }

    /**
     * Returns an array of fields which can be hidden.
     *
     * @return  array
     */
    public static function getDisplayFields()
    {
        return array(
            'category'  =>  array(
                'title' =>  ev_gettext('Category'),
                'required'  =>  1,
            ),
            'priority'  =>    array(
                'title' =>  ev_gettext('Priority'),
                'required'  =>  1,
            ),
            'severity'  =>    array(
                'title' =>  ev_gettext('Severity'),
                'required'  =>  1,
            ),
            'assignment'    =>    array(
                'title' =>  ev_gettext('Assignment'),
                'required'  =>  0,
            ),
            'release'   =>    array(
                'title' =>  ev_gettext('Scheduled Release'),
                'required'  =>  0,
            ),
            'estimated_dev_time'    =>    array(
                'title' =>  ev_gettext('Estimated Dev. Time'),
                'required'  =>  0,
            ),
            'expected_res_date'     =>    array(
                'title' =>  ev_gettext('Expected Resolution Date'),
                'required'  =>  0,
            ),
            'group'     =>    array(
                'title' =>  ev_gettext('Group'),
                'required'  =>  0,
            ),
            'file'  =>    array(
                'title' =>  ev_gettext('File'),
                'required'  =>  0,
            ),
            'private'   =>    array(
                'title' =>  ev_gettext('Private'),
                'required'  =>  0,
            ),
            'product'   =>    array(
                'title' =>  ev_gettext('Product'),
                'required'  =>  0,
            ),
            'associated_issues'   =>    array(
                'title' =>  ev_gettext('Associated Issues'),
                'required'  =>  0,
            ),
        );
    }

    /**
     * Sets the default display fields for a project
     *
     * @param $prj_id
     */
    public static function setDefaultDisplayFields($prj_id)
    {
        $fields = self::getDisplayFields();
        $settings = array();
        foreach ($fields as $field_name => $field_info) {
            $settings[$field_name] = array(
                'required'  =>  $field_info['required'],
                'min_role'  =>  0,
            );
        }
        self::updateFieldDisplaySettings($prj_id, $settings);
    }
}
