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
 * Class to handle project category related issues.
 */
class Category
{
    /**
     * Method used to get the full details of a category.
     *
     * @param   int $prc_id The category ID
     * @return  array The information about the category provided
     */
    public static function getDetails($prc_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `project_category`
                 WHERE
                    prc_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$prc_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to remove user-selected categories from the
     * database.
     *
     * @return  bool Whether the removal worked or not
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $stmt = 'DELETE FROM
                    `project_category`
                 WHERE
                    prc_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update the values stored in the database.
     * Typically the user would modify the title of the category in
     * the application and this method would be called.
     *
     * @return  int 1 if the update worked properly, any other value otherwise
     */
    public static function update()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'UPDATE
                    `project_category`
                 SET
                    prc_title=?
                 WHERE
                    prc_prj_id=? AND
                    prc_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['title'], $_POST['prj_id'], $_POST['id']]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to add a new category to the application.
     *
     * @return  int 1 if the update worked properly, any other value otherwise
     */
    public static function insert()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }

        $stmt = 'INSERT INTO
                    `project_category`
                 (
                    prc_prj_id,
                    prc_title
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['prj_id'], $_POST['title']]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the full list of categories associated with
     * a specific project.
     *
     * @param   int $prj_id The project ID
     * @return  array The full list of categories
     */
    public static function getList($prj_id)
    {
        $stmt = 'SELECT
                    prc_id,
                    prc_title
                 FROM
                    `project_category`
                 WHERE
                    prc_prj_id=?
                 ORDER BY
                    prc_title ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get an associative array of the list of
     * categories associated with a specific project.
     *
     * @param   int $prj_id The project ID
     * @return  array The associative array of categories
     */
    public static function getAssocList($prj_id)
    {
        static $list;

        if (!empty($list[$prj_id])) {
            return $list[$prj_id];
        }

        $stmt = 'SELECT
                    prc_id,
                    prc_title
                 FROM
                    `project_category`
                 WHERE
                    prc_prj_id=?
                 ORDER BY
                    prc_title ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $list[$prj_id] = $res;

        return $res;
    }

    /**
     * Method used to get the title for a specific project category.
     *
     * @param   int $prc_id The category ID
     * @return  string The category title
     */
    public static function getTitle($prc_id)
    {
        $stmt = 'SELECT
                    prc_title
                 FROM
                    `project_category`
                 WHERE
                    prc_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$prc_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }
}
