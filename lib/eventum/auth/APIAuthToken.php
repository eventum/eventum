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

// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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

class APIAuthToken
{
    private static $default_alg = 'HS256';

    public static function generate($usr_id)
    {
        $factory = new RandomLib\Factory();
        $generator = $factory->getMediumStrengthGenerator();
        $token = $generator->generateString(32, \RandomLib\Generator::CHAR_ALNUM);

        self::saveToken($usr_id, $token);

        return $token;
    }

    public static function saveToken($usr_id, $token)
    {
        $sql = 'INSERT INTO
                    {{%api_token}}
                SET
                    apt_usr_id = ?,
                    apt_created = ?,
                    apt_token = ?';
        try {
            $res = DB_Helper::getInstance()->query($sql, array(
                $usr_id,
                Date_Helper::getCurrentDateGMT(),
                $token,
            ));
        } catch (DbException $e) {
            return -1;
        }
    }

    public static function isTokenValidForEmail($token, $email)
    {
        try {
            $usr_id = User::getUserIDByEmail($email, true);
            $active_tokens = self::getTokensForUser($usr_id);
            foreach ($active_tokens as $row) {
                if ($row['token'] == $token) {
                    return true;
                }
            }

            return false;
        } catch (AuthException $e) {
            return false;
        }
    }

    public static function getUserIDByToken($token)
    {
        $sql = "SELECT
                    apt_usr_id
                FROM
                    {{%api_token}}
                WHERE
                    apt_token = ? AND
                    apt_status = 'active'";
        try {
            $usr_id = DB_Helper::getInstance()->getOne($sql, array($token));
        } catch (DbException $e) {
            throw new AuthException('Error fetching user token');
        }
        if (empty($usr_id)) {
            throw new AuthException('Invalid token');
        }

        return $usr_id;
    }

    public static function getTokensForUser($usr_id, $active_only = true, $auto_generate = false)
    {
        $sql = 'SELECT
                    apt_id,
                    apt_usr_id as usr_id,
                    apt_created as created,
                    apt_status as status,
                    apt_token as token
                FROM
                    {{%api_token}}
                WHERE
                    apt_usr_id = ?';
        if ($active_only) {
            $sql .= " AND apt_status='active'";
        }
        $sql .= '
                ORDER BY
                    apt_created DESC';
        try {
            $res = DB_Helper::getInstance()->getAll($sql, array($usr_id));
        } catch (DbException $e) {
            return array();
        }
        if (empty($res)) {
            if ($auto_generate) {
                self::generate($usr_id);

                return self::getTokensForUser($usr_id, false);
            } else {
                return array();
            }
        }

        return $res;
    }

    public static function regenerateKey($usr_id)
    {
        $sql = "UPDATE
                   {{%api_token}}
                SET
                    apt_status = 'revoked'
                WHERE
                    apt_usr_id = ?";
        try {
            DB_Helper::getInstance()->query($sql, array($usr_id));
        } catch (DbException $e) {
            return -1;
        }

        $res = self::generate($usr_id);

        return 1;
    }
}
