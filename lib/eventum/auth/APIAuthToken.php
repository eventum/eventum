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

class APIAuthToken
{
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
                    `api_token`
                SET
                    apt_usr_id = ?,
                    apt_created = ?,
                    apt_token = ?';
        try {
            $res = DB_Helper::getInstance()->query($sql, [
                $usr_id,
                Date_Helper::getCurrentDateGMT(),
                $token,
            ]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    public static function isTokenValidForEmail($token, $email)
    {
        try {
            $usr_id = User::getUserIDByEmail($email, true);
            $active_tokens = self::getTokensForUser($usr_id);
            foreach ($active_tokens as $row) {
                if ($row['token'] === $token) {
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
                    `api_token`
                WHERE
                    apt_token = ? AND
                    apt_status = 'active'";
        try {
            $usr_id = DB_Helper::getInstance()->getOne($sql, [$token]);
        } catch (DatabaseException $e) {
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
                    `api_token`
                WHERE
                    apt_usr_id = ?';
        if ($active_only) {
            $sql .= " AND apt_status='active'";
        }
        $sql .= '
                ORDER BY
                    apt_created DESC';
        try {
            $res = DB_Helper::getInstance()->getAll($sql, [$usr_id]);
        } catch (DatabaseException $e) {
            return [];
        }
        if (empty($res)) {
            if ($auto_generate) {
                self::generate($usr_id);

                return self::getTokensForUser($usr_id, false);
            }

            return [];
        }

        return $res;
    }

    public static function regenerateKey($usr_id)
    {
        $sql = "UPDATE
                   `api_token`
                SET
                    apt_status = 'revoked'
                WHERE
                    apt_usr_id = ?";
        try {
            DB_Helper::getInstance()->query($sql, [$usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        self::generate($usr_id);

        return 1;
    }
}
