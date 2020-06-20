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

use Eventum\Db\DatabaseException;

/**
 * Class designed to handle adding, removing and viewing authorized repliers for an issue.
 */
class Authorized_Replier
{
    /**
     * Method used to get the full list of users (the full names) authorized to
     * reply to emails in a given issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of users
     */
    public static function getAuthorizedRepliers(int $issue_id): array
    {
        $system_user_id = Setup::getSystemUserId();

        // split into users and others (those with email address but no real user accounts)
        $repliers = [
            'users' => [],
            'other' => [],
        ];

        $stmt = "SELECT
                    iur_id,
                    iur_usr_id,
                    usr_email,
                    if (iur_usr_id = ?, iur_email, usr_full_name) replier,
                    if (iur_usr_id = ?, 'other', 'user') replier_type
                 FROM
                    `issue_user_replier`,
                    `user`
                 WHERE
                    iur_iss_id=? AND
                    iur_usr_id=usr_id";

        $params = [$system_user_id, $system_user_id, $issue_id];
        $res = DB_Helper::getInstance()->getAll($stmt, $params);

        // split into users and others (those with email address but no real user accounts)
        $names = [];
        if (count($res) > 0) {
            foreach ($res as $row) {
                if ($row['iur_usr_id'] == $system_user_id) {
                    $repliers['other'][] = $row;
                } else {
                    $repliers['users'][] = $row;
                }
                $names[] = $row['replier'];
            }
        }
        $repliers['all'] = array_merge($repliers['users'], $repliers['other']);

        return [
            $names,
            $repliers,
        ];
    }

    /**
     * Removes the specified authorized repliers from issue
     *
     * @param int $issue_id
     * @param int[] $iur_ids The ids of the authorized repliers
     */
    public static function removeRepliers(int $issue_id, array $iur_ids): void
    {
        $usr_id = Auth::getUserID();
        $db = DB_Helper::getInstance();

        foreach ($iur_ids as $iur_id) {
            $replier = self::getReplier($iur_id);
            $stmt = 'DELETE FROM
                        `issue_user_replier`
                     WHERE
                        iur_iss_id = ? AND
                        iur_id = ?';
            $db->query($stmt, [$issue_id, $iur_id]);

            History::add($issue_id, $usr_id, 'replier_removed', 'Authorized replier {replier} removed by {user}', [
                'replier' => $replier,
                'user' => User::getFullName($usr_id),
            ]);
        }
    }

    /**
     * Adds the specified email address to the list of authorized users.
     *
     * @param   int $issue_id the id of the issue
     * @param   string $email the email of the user
     * @param   bool $add_history if this should be logged
     * @return int
     */
    public static function manualInsert($issue_id, $email, $add_history = true)
    {
        $email = Mail_Helper::getEmailAddress($email);

        if (Validation::isWhitespace($email)) {
            return -1;
        }

        if (self::isAuthorizedReplier($issue_id, $email)) {
            return -1;
        }

        $prj_id = Issue::getProjectID($issue_id);
        $workflow = Workflow::handleAuthorizedReplierAdded($prj_id, $issue_id, $email);
        if ($workflow === false) {
            // cancel subscribing the user
            return -1;
        }

        // first check if this is an actual user or just an email address
        $usr_id = User::getUserIDByEmail($email, true);
        if (!empty($usr_id)) {
            return self::addUser($issue_id, $usr_id, $add_history);
        }

        $stmt = 'INSERT INTO
                    `issue_user_replier`
                 (
                    iur_iss_id,
                    iur_usr_id,
                    iur_email
                 ) VALUES (
                    ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$issue_id, Setup::getSystemUserId(), $email]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($add_history) {
            // add the change to the history of the issue
            $usr_id = Auth::getUserID();
            History::add($issue_id, $usr_id, 'replier_other_added', '{email} added to the authorized repliers list by {user}', [
                'email' => $email,
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Adds a real user to the authorized repliers list.
     *
     * @param   int $issue_id the id of the issue
     * @param   int $usr_id the id of the user
     * @param   bool $add_history if this should be logged
     */
    public static function addUser($issue_id, $usr_id, $add_history = true)
    {
        // don't add customers to this list. They should already be able to send
        if (User::getRoleByUser($usr_id, Issue::getProjectID($issue_id)) == User::ROLE_CUSTOMER) {
            return -2;
        }

        $stmt = 'INSERT INTO
                    `issue_user_replier`
                 (
                    iur_iss_id,
                    iur_usr_id
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$issue_id, $usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($add_history) {
            // add the change to the history of the issue
            $current_usr_id = Auth::getUserID();
            History::add($issue_id, $current_usr_id, 'replier_added', '{other_user} added to the authorized repliers list by {user}', [
                'other_user' => User::getFullName($usr_id),
                'user' => User::getFullName($current_usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Returns if the specified user is authorized to reply to this issue.
     *
     * @param   int $issue_id the id of the issue
     * @param   string  $email the email address to check
     * @return  bool if the specified user is allowed to reply to the issue
     */
    public static function isAuthorizedReplier($issue_id, $email)
    {
        // XXX: Add caching

        $email = Mail_Helper::getEmailAddress($email);
        // first check if this is an actual user or just an email address
        $usr_id = User::getUserIDByEmail($email, true);
        if (!empty($usr_id)) {
            // real user, get id
            $is_usr_authorized = self::isUserAuthorizedReplier($issue_id, $usr_id);
            if ($is_usr_authorized) {
                return true;
            }
            // if user is not authorized by user ID, continue to check by email in case the user account was added
            // after the email address was added to authorized repliers list.
        }
        // not a real user
        $stmt = 'SELECT
                    COUNT(*) AS total
                 FROM
                    `issue_user_replier`
                 WHERE
                    iur_iss_id=? AND
                    iur_email=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id, $email]);
        } catch (DatabaseException $e) {
            return false;
        }

        if ($res > 0) {
            return true;
        }

        return false;
    }

    /**
     * Returns if the specified usr_id is authorized to reply.
     *
     * @param   int $issue_id The id of the issue
     * @param   int $usr_id the id of the user
     * @return  bool if the user is authorized to reply
     */
    public static function isUserAuthorizedReplier($issue_id, $usr_id)
    {
        $stmt = 'SELECT
                    count(iur_id)
                 FROM
                    `issue_user_replier`
                 WHERE
                    iur_iss_id = ? AND
                    iur_usr_id = ?';
        $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id, $usr_id]);

        return $res > 0;
    }

    /**
     * Returns the replier based on the iur_id
     *
     * @param   int $iur_id The id of the authorized replier
     * @return  string The name/email of the replier
     */
    public static function getReplier($iur_id)
    {
        $stmt = "SELECT
                    if (iur_usr_id = '" . Setup::getSystemUserId() . "', iur_email, usr_full_name) replier
                 FROM
                    `issue_user_replier`,
                    `user`
                 WHERE
                    iur_usr_id = usr_id AND
                    iur_id = ?";

        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$iur_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to remotely add an authorized replier to a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $usr_id The user ID of the person performing the change
     * @param   bool $replier The user ID of the authorized replier
     * @return  int The status ID
     */
    public static function remoteAddAuthorizedReplier($issue_id, $usr_id, $replier)
    {
        $res = self::manualInsert($issue_id, $replier, false);
        if ($res !== -1) {
            // save a history entry about this...
            History::add($issue_id, $usr_id, 'remote_replier_added', '{replier} remotely added to authorized repliers by {user}', [
                'replier' => $replier,
                'user' => User::getFullName($usr_id),
            ]);
        }

        return $res;
    }
}
