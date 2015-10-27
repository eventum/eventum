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


class Search_Profile
{
    /**
     * Method used to remove the search profile record for this user,
     * for the specified project and profile type.
     *
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @return  boolean
     */
    public static function remove($usr_id, $prj_id, $type)
    {
        $stmt = 'DELETE FROM
                    {{%search_profile}}
                 WHERE
                    sep_usr_id=? AND
                    sep_prj_id=? AND
                    sep_type=?';
        try {
            DB_Helper::getInstance()->query($stmt, array($usr_id, $prj_id, $type));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to retrieve a search profile record for this user,
     * for the specified project and profile type.
     *
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
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
                    {{%search_profile}}
                 WHERE
                    sep_usr_id=? AND
                    sep_prj_id=? AND
                    sep_type=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($usr_id, $prj_id, $type));
        } catch (DbException $e) {
            return array();
        }

        if (empty($res)) {
            return array();
        }

        $returns[$usr_id][$prj_id][$type] = unserialize($res);

        return unserialize($res);
    }

    /**
     * Method used to check whether a search profile already exists
     * or not.
     *
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @return  boolean
     */
    private static function _exists($usr_id, $prj_id, $type)
    {
        $stmt = 'SELECT
                    COUNT(*) AS total
                 FROM
                    {{%search_profile}}
                 WHERE
                    sep_usr_id=? AND
                    sep_prj_id=? AND
                    sep_type=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($usr_id, $prj_id, $type));
        } catch (DbException $e) {
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
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  boolean
     */
    public static function save($usr_id, $prj_id, $type, $profile)
    {
        if (!self::_exists($usr_id, $prj_id, $type)) {
            return self::_insert($usr_id, $prj_id, $type, $profile);
        } else {
            return self::_update($usr_id, $prj_id, $type, $profile);
        }
    }

    /**
     * Method used to create a new search profile record for this
     * user, for the specified project, and profile type.
     *
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  boolean
     */
    private function _insert($usr_id, $prj_id, $type, $profile)
    {
        $stmt = 'INSERT INTO
                    {{%search_profile}}
                 (
                    sep_usr_id,
                    sep_prj_id,
                    sep_type,
                    sep_user_profile
                 ) VALUES (
                    ?, ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, array($usr_id, $prj_id, $type, serialize($profile)));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update an existing search profile record for
     * this user, for the specified project, and profile type.
     *
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  boolean
     */
    private static function _update($usr_id, $prj_id, $type, $profile)
    {
        $stmt = 'UPDATE
                    {{%search_profile}}
                 SET
                    sep_user_profile=?
                 WHERE
                    sep_usr_id=? AND
                    sep_prj_id=? AND
                    sep_type=?';

        try {
            DB_Helper::getInstance()->query($stmt, array(serialize($profile), $usr_id, $prj_id, $type));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }
}
