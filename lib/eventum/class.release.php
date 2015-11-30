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

/**
 * Class to handle the business logic related to the administration
 * of releases in the system.
 */
class Release
{
    /**
     * Method used to check whether a release is assignable or not.
     *
     * @param   integer $pre_id The release ID
     * @return  boolean
     */
    public static function isAssignable($pre_id)
    {
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    {{%project_release}}
                 WHERE
                    pre_id=? AND
                    pre_status=?';

        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($pre_id, 'available'));
        } catch (DbException $e) {
            return false;
        }

        if ($res == 0) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the details of a specific release.
     *
     * @param   integer $pre_id The release ID
     * @return  array The details of the release
     */
    public static function getDetails($pre_id)
    {
        $stmt = 'SELECT
                    *,
                    MONTH(pre_scheduled_date) AS scheduled_month,
                    YEAR(pre_scheduled_date) AS scheduled_year
                 FROM
                    {{%project_release}}
                 WHERE
                    pre_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($pre_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the title of a specific release.
     *
     * @param   integer $pre_id The release ID
     * @return  string The title of the release
     */
    public static function getTitle($pre_id)
    {
        $stmt = 'SELECT
                    pre_title
                 FROM
                    {{%project_release}}
                 WHERE
                    pre_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($pre_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to remove all releases associated with a specific
     * set of projects.
     *
     * @param   array $ids The list of projects
     * @return  boolean
     */
    public static function removeByProjects($ids)
    {
        $stmt = 'DELETE FROM
                    {{%project_release}}
                 WHERE
                    pre_prj_id IN (' . DB_Helper::buildList($ids) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $ids);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove releases by using the administrative
     * interface of the system.
     *
     * @return  boolean
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $itemlist = DB_Helper::buildList($items);

        // gotta fix the issues that are using this release
        $stmt = "UPDATE
                    {{%issue}}
                 SET
                    iss_pre_id=0
                 WHERE
                    iss_pre_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    {{%project_release}}
                 WHERE
                    pre_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update the release by using the administrative
     * interface of the system.
     *
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    public static function update()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $scheduled_date = $_POST['scheduled_date']['Year'] . '-' . $_POST['scheduled_date']['Month'] . '-' . $_POST['scheduled_date']['Day'];
        $stmt = 'UPDATE
                    {{%project_release}}
                 SET
                    pre_title=?,
                    pre_scheduled_date=?,
                    pre_status=?
                 WHERE
                    pre_prj_id=? AND
                    pre_id=?';
        $params = array($_POST['title'], $scheduled_date, $_POST['status'], $_POST['prj_id'], $_POST['id']);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to add a new release by using the administrative
     * interface of the system.
     *
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    public static function insert()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $scheduled_date = $_POST['scheduled_date']['Year'] . '-' . $_POST['scheduled_date']['Month'] . '-' . $_POST['scheduled_date']['Day'];
        $stmt = 'INSERT INTO
                    {{%project_release}}
                 (
                    pre_prj_id,
                    pre_title,
                    pre_scheduled_date,
                    pre_status
                 ) VALUES (
                    ?, ?, ?, ?
                 )';
        $params = array(
            $_POST['prj_id'],
            $_POST['title'],
            $scheduled_date,
            $_POST['status'],
        );
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the list of releases associated with a
     * specific project.
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of releases
     */
    public static function getList($prj_id)
    {
        $stmt = 'SELECT
                    pre_id,
                    pre_title,
                    pre_scheduled_date,
                    pre_status
                 FROM
                    {{%project_release}}
                 WHERE
                    pre_prj_id=?
                 ORDER BY
                    pre_scheduled_date ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get a list as an associative array of the
     * releases.
     *
     * @param   integer $prj_id The project ID
     * @param   boolean $show_all_dates If true all releases, not just those with future dates will be returned
     * @return  array The list of releases
     */
    public static function getAssocList($prj_id, $show_all_dates = false)
    {
        $stmt = 'SELECT
                    pre_id,
                    pre_title
                 FROM
                    {{%project_release}}
                 WHERE
                    pre_prj_id=? AND
                    (
                      pre_status=?';
        $params = array($prj_id, 'available');
        if ($show_all_dates != true) {
            $stmt .= ' AND
                      pre_scheduled_date >= ?';
            $params[] = gmdate('Y-m-d');
        }
        $stmt .= '
                    )
                 ORDER BY
                    pre_scheduled_date ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $params);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }
}
