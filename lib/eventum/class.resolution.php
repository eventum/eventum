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
 * Class to handle the business logic related to the administration
 * of resolutions in the system.
 */
class Resolution
{
    /**
     * Method used to get the title of a specific resolution.
     *
     * @param   int $res_id The resolution ID
     * @return  string The title of the resolution
     */
    public static function getTitle($res_id)
    {
        $stmt = 'SELECT
                    res_title
                 FROM
                    `resolution`
                 WHERE
                    res_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$res_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the id of a specific resolution.
     *
     * @param   string  $title The resolution title
     * @return  int id The id of the resolution
     */
    public static function getID($title)
    {
        $stmt = 'SELECT
                    res_id
                 FROM
                    `resolution`
                 WHERE
                    res_title=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$title]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to remove resolutions by using the administrative
     * interface of the system.
     *
     * @return  bool
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $itemlist = DB_Helper::buildList($items);
        // gotta fix the issues before removing the resolution
        $stmt = "UPDATE
                    `issue`
                 SET
                    iss_res_id=0
                 WHERE
                    iss_res_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    `resolution`
                 WHERE
                    res_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update the resolution by using the administrative
     * interface of the system.
     *
     * @return  int 1 if the update worked, -1 or -2 otherwise
     */
    public static function update()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'UPDATE
                    `resolution`
                 SET
                    res_title=?,
                    res_rank=?
                 WHERE
                    res_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['title'], $_POST['rank'], $_POST['id']]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the details of a specific resolution.
     *
     * @param   int $res_id The resolution ID
     * @return  array The details of the resolution
     */
    public static function getDetails($res_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `resolution`
                 WHERE
                    res_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$res_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the full list of resolutions.
     *
     * @return  array The list of resolutions
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    res_id,
                    res_rank,
                    res_title
                 FROM
                    `resolution`
                 ORDER BY
                    res_rank ASC,
                    res_title ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get a list as an associative array of the
     * resolutions.
     *
     * @return  array The list of resolutions
     */
    public static function getAssocList()
    {
        $stmt = 'SELECT
                    res_id,
                    res_title
                 FROM
                    `resolution`
                 ORDER BY
                    res_rank ASC,
                    res_title ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to add a new resolution by using the administrative
     * interface of the system.
     *
     * @return  int 1 if the update worked, -1 or -2 otherwise
     */
    public static function insert()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'INSERT INTO
                    `resolution`
                 (
                    res_title,
                    res_rank,
                    res_created_date
                 ) VALUES (
                    ?, ?, ?
                 )';
        $params = [$_POST['title'], $_POST['rank'], Date_Helper::getCurrentDateGMT()];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }
}
