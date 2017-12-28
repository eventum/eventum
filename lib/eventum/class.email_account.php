<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

use Eventum\Crypto\CryptoManager;
use Eventum\Crypto\EncryptedValue;
use Eventum\Db\DatabaseException;

class Email_Account
{
    /**
     * Method used to get the options related to the auto creation of
     * new issues.
     *
     * @param   int $ema_id The email account ID
     * @return  array The issue auto creation options
     */
    public static function getIssueAutoCreationOptions($ema_id)
    {
        $stmt = 'SELECT
                    ema_issue_auto_creation_options
                 FROM
                    `email_account`
                 WHERE
                    ema_id=?';
        $res = DB_Helper::getInstance()->getOne($stmt, [$ema_id]);

        if (!is_string($res)) {
            $res = (string)$res;
        }

        return unserialize($res);
    }

    /**
     * Method used to update the issue auto creation related options.
     *
     * @param   int $ema_id The email account ID
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function updateIssueAutoCreation($ema_id, $auto_creation, $options)
    {
        $stmt = 'UPDATE
                    `email_account`
                 SET
                    ema_issue_auto_creation=?,
                    ema_issue_auto_creation_options=?
                 WHERE
                    ema_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$auto_creation, @serialize($options), $ema_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the support email account associated with a given
     * support email message.
     *
     * @param   int $sup_id The support email ID
     * @return  int The email account ID
     */
    public static function getAccountByEmail($sup_id)
    {
        $stmt = 'SELECT
                    sup_ema_id
                 FROM
                    `support_email`
                 WHERE
                    sup_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$sup_id]);
        } catch (DatabaseException $e) {
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
     * @return  int The support email account ID
     */
    public static function getAccountID($username, $hostname, $mailbox)
    {
        $stmt = 'SELECT
                    ema_id
                 FROM
                    `email_account`
                 WHERE
                    ema_username=? AND
                    ema_hostname=?';
        try {
            $params = [$username, $hostname];
            if ($mailbox !== null) {
                $stmt .= ' AND ema_folder=?';
                $params[] = $mailbox;
            }
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
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
     * @param   int $ema_id The support email account ID
     * @return  int The project ID
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
     * @param   int $ema_id The support email account ID
     * @param bool $include_password
     * @return  array The account details
     */
    public static function getDetails($ema_id, $include_password = false)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `email_account`
                 WHERE
                    ema_id=?';

        // IMPORTANT: do not print out $ema_id without sanitizing, it may contain XSS
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$ema_id]);
        } catch (DatabaseException $e) {
            throw new RuntimeException('email account not found');
        }

        if (!$res) {
            throw new RuntimeException('email account not found');
        }

        $res['ema_issue_auto_creation_options'] = @unserialize($res['ema_issue_auto_creation_options']);
        if ($include_password) {
            $res['ema_password'] = new EncryptedValue($res['ema_password']);
        } else {
            unset($res['ema_password']);
        }

        return $res;
    }

    /**
     * Method used to remove the specified support email accounts.
     *
     * @return  bool
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $stmt = 'DELETE FROM
                    `email_account`
                 WHERE
                    ema_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        Support::removeEmailByAccounts($items);

        return true;
    }

    /**
     * Method used to add a new support email account.
     *
     * @return  int 1 if the update worked, -1 otherwise
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
                    `email_account`
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
        $params = [
            $_POST['project'],
            $_POST['type'],
            $_POST['hostname'],
            $_POST['port'],
            @$_POST['folder'],
            $_POST['username'],
            CryptoManager::encrypt($_POST['password']),
            $_POST['get_only_new'],
            $_POST['leave_copy'],
            $_POST['use_routing'],
        ];

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to update a support email account details.
     *
     * @return  int 1 if the update worked, -1 otherwise
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
                    `email_account`
                 SET
                    ema_prj_id=?,
                    ema_type=?,
                    ema_hostname=?,
                    ema_port=?,
                    ema_folder=?,
                    ema_username=?,
                    ema_get_only_new=?,
                    ema_leave_copy=?,
                    ema_use_routing=?
                 WHERE
                    ema_id=?';
        $params = [
            $_POST['project'],
            $_POST['type'],
            $_POST['hostname'],
            $_POST['port'],
            @$_POST['folder'],
            $_POST['username'],
            $_POST['get_only_new'],
            $_POST['leave_copy'],
            $_POST['use_routing'],
            $_POST['id'],
        ];

        try {
            DB_Helper::getInstance()->query($stmt, $params);
            if (!empty($_POST['password'])) {
                self::updatePassword($_POST['id'], $_POST['password']);
            }
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Update password for specified email account
     *
     * @param int $ema_id
     * @param string $password plain text password
     */
    public static function updatePassword($ema_id, $password)
    {
        $stmt = 'UPDATE
                    `email_account`
                 SET
                    ema_password=?
                 WHERE
                    ema_id=?';
        $params = [
            CryptoManager::encrypt($password),
            $ema_id,
        ];

        DB_Helper::getInstance()->query($stmt, $params);
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
                    `email_account`
                 ORDER BY
                    ema_hostname';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['prj_title'] = Project::getName($row['ema_prj_id']);

            // do not expose as not needed
            unset($row['ema_password']);
        }

        return $res;
    }

    /**
     * Method used to get an associative array of the support email
     * accounts in the format of account ID => account title.
     *
     * @param   int $projects An array of project IDs
     * @return  array The list of accounts
     */
    public static function getAssocList($projects, $include_project_title = false)
    {
        if (!is_array($projects)) {
            $projects = [$projects];
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
                    `email_account`,
                    `project`
                 WHERE
                    prj_id = ema_prj_id AND
                    ema_prj_id IN (" . DB_Helper::buildList($projects) . ')
                 ORDER BY
                    ema_title';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $projects);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the first support email account associated
     * with the current activated project.
     *
     * @param   int $prj_id The ID of the project. If blank the currently project will be used.
     * @return  int The email account ID
     */
    public static function getEmailAccount($prj_id = false)
    {
        if ($prj_id == false) {
            $prj_id = Auth::getCurrentProject();
        }
        $stmt = 'SELECT
                    ema_id
                 FROM
                    `email_account`
                 WHERE
                    ema_prj_id=?
                 LIMIT
                    1 OFFSET 0';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }
}
