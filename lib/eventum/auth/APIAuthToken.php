<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
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

use \Firebase\JWT\JWT;

class APIAuthToken
{
    private static $default_alg = "HS256";

    public static function generate($usr_id)
    {
        $expires = date_add(Date_Helper::getDateTime(), date_interval_create_from_date_string('6 months'));

        $token = JWT::encode(array(
            'iss'   =>  APP_BASE_URL,
            'usr_id'    =>  $usr_id,
            'exp'   =>  $expires->format('U'),
        ), Auth::privateKey());

        self::saveToken($usr_id, $token, $expires);

        return $token;
    }

    public static function saveToken($usr_id, $token, $expires)
    {
        $sql = "INSERT INTO
                    {{%api_token}}
                SET
                    apt_usr_id = ?,
                    apt_created = ?,
                    apt_token = ?,
                    apt_expires = ?";
        try {
            $res = DB_Helper::getInstance()->query($sql, array(
                $usr_id,
                Date_Helper::getCurrentDateGMT(),
                $token,
                Date_Helper::getSqlDateTime($expires)
            ));
        } catch (DbException $e) {
            return -1;
        }
    }

    public static function checkTokenStatus($token)
    {
        $sql = "SELECT
                    apt_usr_id
                FROM
                    {{%api_token}}
                WHERE
                    apt_token = ? AND
                    apt_status = 'active' AND
                    apt_expires > ?";
        try {
            $usr_id = DB_Helper::getInstance()->getOne($sql, array($token, Date_Helper::getCurrentDateGMT()));
        } catch (DbException $e) {
            throw new AuthException("Error fetching user token");
        }
        if (empty($usr_id)) {
            throw new AuthException("Invalid or expired user token");
        }
        return $usr_id;
    }

    public static function getUserIDByToken($token)
    {
        try {
            // check that token is valid and not expired
            $decoded = JWT::decode($token, Auth::privateKey(), array(self::$default_alg));

            // make sure token hasn't been revoked
            $usr_id = self::checkTokenStatus($token);

            return $usr_id;
        } catch(Exception $e) {
            throw new AuthException($e);
        }
    }

    public static function getTokensForUser($usr_id, $auto_generate = false)
    {
        $sql = "SELECT
                    apt_id,
                    apt_usr_id as usr_id,
                    apt_created as created,
                    apt_expires as expires,
                    apt_status as status,
                    apt_token as token
                FROM
                    {{%api_token}}
                WHERE
                    apt_usr_id = ?
                ORDER BY
                    apt_created DESC";
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