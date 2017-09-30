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

class Search_Profile
{
    /**
     * Method used to remove the search profile record for this user,
     * for the specified project and profile type.
     *
     * @param   int $usr_id The user ID
     * @param   int $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @return  bool
     */
    public static function remove($usr_id, $prj_id, $type)
    {
        $stmt = 'DELETE FROM
                    `search_profile`
                 WHERE
                    sep_usr_id=? AND
                    sep_prj_id=? AND
                    sep_type=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$usr_id, $prj_id, $type]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to retrieve a search profile record for this user,
     * for the specified project and profile type.
     *
     * @param   int $usr_id The user ID
     * @param   int $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @return  array The user's search profile
     */
    public static function getProfile($usr_id, $prj_id, $type)
    {
        static $returns;

        if (!empty($returns[$usr_id][$prj_id][$type])) {
            return $returns[$usr_id][$prj_id][$type];
        }

        $stmt = 'SELECT
                    sep_user_profile
                 FROM
                    `search_profile`
                 WHERE
                    sep_usr_id=? AND
                    sep_prj_id=? AND
                    sep_type=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$usr_id, $prj_id, $type]);
        } catch (DatabaseException $e) {
            return [];
        }

        if (empty($res)) {
            return [];
        }

        $returns[$usr_id][$prj_id][$type] = unserialize($res);

        return unserialize($res);
    }

    /**
     * Method used to check whether a search profile already exists
     * or not.
     *
     * @param   int $usr_id The user ID
     * @param   int $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @return  bool
     */
    private static function _exists($usr_id, $prj_id, $type)
    {
        $stmt = 'SELECT
                    COUNT(*) AS total
                 FROM
                    `search_profile`
                 WHERE
                    sep_usr_id=? AND
                    sep_prj_id=? AND
                    sep_type=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$usr_id, $prj_id, $type]);
        } catch (DatabaseException $e) {
            return false;
        }

        if ($res > 0) {
            return true;
        }

        return false;
    }

    /**
     * Method used to save a search profile record for this user, for
     * the specified project, and profile type.
     *
     * @param   int $usr_id The user ID
     * @param   int $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  bool
     */
    public static function save($usr_id, $prj_id, $type, $profile)
    {
        if (!self::_exists($usr_id, $prj_id, $type)) {
            return self::_insert($usr_id, $prj_id, $type, $profile);
        }

        return self::_update($usr_id, $prj_id, $type, $profile);
    }

    /**
     * Method used to create a new search profile record for this
     * user, for the specified project, and profile type.
     *
     * @param   int $usr_id The user ID
     * @param   int $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  bool
     */
    private static function _insert($usr_id, $prj_id, $type, $profile)
    {
        $stmt = 'INSERT INTO
                    `search_profile`
                 (
                    sep_usr_id,
                    sep_prj_id,
                    sep_type,
                    sep_user_profile
                 ) VALUES (
                    ?, ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$usr_id, $prj_id, $type, serialize($profile)]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update an existing search profile record for
     * this user, for the specified project, and profile type.
     *
     * @param   int $usr_id The user ID
     * @param   int $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  bool
     */
    private static function _update($usr_id, $prj_id, $type, $profile)
    {
        $stmt = 'UPDATE
                    `search_profile`
                 SET
                    sep_user_profile=?
                 WHERE
                    sep_usr_id=? AND
                    sep_prj_id=? AND
                    sep_type=?';

        try {
            DB_Helper::getInstance()->query($stmt, [serialize($profile), $usr_id, $prj_id, $type]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }
}
