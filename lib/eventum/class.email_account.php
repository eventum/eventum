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
use Eventum\Db\DatabaseException;
use Eventum\Db\Doctrine;

class Email_Account
{
    /**
     * Method used to get the options related to the auto creation of
     * new issues.
     *
     * @param   int $ema_id The email account ID
     * @return  array The issue auto creation options
     */
    public static function getIssueAutoCreationOptions(int $ema_id): ?array
    {
        $repo = Doctrine::getEmailAccountRepository();
        $account = $repo->findById($ema_id);

        return $account->getIssueAutoCreationOptions();
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
     * Method used to get the project ID associated with a given email account.
     *
     * @param   int $ema_id The support email account ID
     * @return  int The project ID
     */
    public static function getProjectID(int $ema_id): int
    {
        $repo = Doctrine::getEmailAccountRepository();
        $account = $repo->findById($ema_id);

        return $account->getProjectId();
    }

    /**
     * Method used to get the details of a given support email
     * account.
     *
     * @param   int $ema_id The support email account ID
     * @param bool $include_password
     * @return  array The account details
     */
    public static function getDetails(int $ema_id, bool $include_password = false): array
    {
        $repo = Doctrine::getEmailAccountRepository();
        $account = $repo->findById($ema_id);
        $res = $account->toArray();

        if ($include_password) {
            $res['ema_password'] = $account->getPassword();
        }

        return $res;
    }

    /**
     * Update password for specified email account
     *
     * @param int $ema_id
     * @param string $password plain text password
     */
    public static function updatePassword($ema_id, $password): void
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
    public static function getEmailAccount(int $prj_id = null): ?int
    {
        if (!$prj_id) {
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

        return DB_Helper::getInstance()->getOne($stmt, [$prj_id]) ?: null;
    }
}
