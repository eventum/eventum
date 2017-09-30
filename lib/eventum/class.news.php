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

class News
{
    /**
     * Method used to get the list of news entries available in the
     * system for a given project.
     *
     * @param   int $prj_id The project ID
     * @return  array The list of news entries
     */
    public static function getListByProject($prj_id, $show_full_message = false)
    {
        $stmt = "SELECT
                    *
                 FROM
                    `news`,
                    `project_news`
                 WHERE
                    prn_nws_id=nws_id AND
                    prn_prj_id=? AND
                    nws_status='active'
                 ORDER BY
                    nws_created_date DESC
                 LIMIT
                    3 OFFSET 0";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            if ((!$show_full_message) && (strlen($row['nws_message']) > 300)) {
                $next_space = strpos($row['nws_message'], ' ', 254);
                if (empty($next_space)) {
                    $next_space = strpos($row['nws_message'], "\n", 254);
                }
                if (($next_space > 0) && (($next_space - 255) < 50)) {
                    $cut = $next_space;
                } else {
                    $cut = 255;
                }
                $row['nws_message'] = substr($row['nws_message'], 0, $cut) . '...';
            }
            $row['nws_message'] = nl2br(htmlspecialchars($row['nws_message']));
        }

        return $res;
    }

    /**
     * Method used to add a project association to a news entry.
     *
     * @param   int $nws_id The news ID
     * @param   int $prj_id The project ID
     */
    public static function addProjectAssociation($nws_id, $prj_id)
    {
        $stmt = 'INSERT INTO
                    `project_news`
                 (
                    prn_nws_id,
                    prn_prj_id
                 ) VALUES (
                    ?, ?
                 )';
        DB_Helper::getInstance()->query($stmt, [$nws_id, $prj_id]);
    }

    /**
     * Method used to add a news entry to the system.
     *
     * @return  int 1 if the insert worked, -1 otherwise
     */
    public static function insert()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        if (Validation::isWhitespace($_POST['message'])) {
            return -3;
        }
        $stmt = 'INSERT INTO
                    `news`
                 (
                    nws_usr_id,
                    nws_created_date,
                    nws_title,
                    nws_message,
                    nws_status
                 ) VALUES (
                    ?, ?, ?, ?, ?
                 )';
        $params = [
            Auth::getUserID(),
            Date_Helper::getCurrentDateGMT(),
            $_POST['title'],
            $_POST['message'],
            $_POST['status'],
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $new_news_id = DB_Helper::get_last_insert_id();
        // now populate the project-news mapping table
        foreach ($_POST['projects'] as $prj_id) {
            self::addProjectAssociation($new_news_id, $prj_id);
        }

        return 1;
    }

    /**
     * Method used to remove a news entry from the system.
     *
     * @return  bool
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $itemlist = DB_Helper::buildList($items);
        $stmt = "DELETE FROM
                    `news`
                 WHERE
                    nws_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        self::removeProjectAssociations($items);

        return true;
    }

    /**
     * Method used to remove the project associations for a given
     * news entry.
     *
     * @param   int $nws_id The news ID
     * @param   int $prj_id The project ID
     * @return  bool
     */
    public static function removeProjectAssociations($nws_id, $prj_id = false)
    {
        if (!is_array($nws_id)) {
            $nws_id = [$nws_id];
        }

        $items = DB_Helper::buildList($nws_id);
        $stmt = "DELETE FROM
                    `project_news`
                 WHERE
                    prn_nws_id IN ($items)";
        $params = $nws_id;
        if ($prj_id) {
            $stmt .= ' AND prn_prj_id=?';
            $params[] = $prj_id;
        }
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update a news entry in the system.
     *
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function update()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        if (Validation::isWhitespace($_POST['message'])) {
            return -3;
        }
        $stmt = 'UPDATE
                    `news`
                 SET
                    nws_title=?,
                    nws_message=?,
                    nws_status=?
                 WHERE
                    nws_id=?';
        $params = [$_POST['title'], $_POST['message'], $_POST['status'], $_POST['id']];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        // remove all of the associations with projects, then add them all again
        self::removeProjectAssociations($_POST['id']);
        foreach ($_POST['projects'] as $prj_id) {
            self::addProjectAssociation($_POST['id'], $prj_id);
        }

        return 1;
    }

    /**
     * Method used to get the details of a news entry for a given news ID.
     *
     * @param   int $nws_id The news entry ID
     * @return  array The news entry details
     */
    public static function getDetails($nws_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `news`
                 WHERE
                    nws_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$nws_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        // get all of the project associations here as well
        $res['projects'] = array_keys(self::getAssociatedProjects($res['nws_id']));
        $res['nws_message'] = nl2br(htmlspecialchars($res['nws_message']));

        return $res;
    }

    /**
     * Method used to get the details of a news entry for a given news ID.
     *
     * @param   int $nws_id The news entry ID
     * @return  array The news entry details
     */
    public static function getAdminDetails($nws_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `news`
                 WHERE
                    nws_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$nws_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        // get all of the project associations here as well
        $res['projects'] = array_keys(self::getAssociatedProjects($res['nws_id']));

        return $res;
    }

    /**
     * Method used to get the list of news entries available in the system.
     *
     * @return  array The list of news entries
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    nws_id,
                    nws_title,
                    nws_status
                 FROM
                    `news`
                 ORDER BY
                    nws_title ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        // get the list of associated projects
        foreach ($res as &$row) {
            $row['projects'] = implode(', ', array_values(self::getAssociatedProjects($row['nws_id'])));
        }

        return $res;
    }

    /**
     * Method used to get the list of associated projects for a given
     * news entry.
     *
     * @param   int $nws_id The news ID
     * @return  array The list of projects
     */
    public static function getAssociatedProjects($nws_id)
    {
        $stmt = 'SELECT
                    prj_id,
                    prj_title
                 FROM
                    `project`,
                    `project_news`
                 WHERE
                    prj_id=prn_prj_id AND
                    prn_nws_id=?';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$nws_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }
}
