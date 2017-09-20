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
 * Class to handle issue severity
 */
class Severity
{
    /**
     * Method used to quickly change the ranking of a severity entry
     * from the administration screen.
     *
     * @param   int $prj_id The project ID
     * @param   int $sev_id The severityID
     * @param   string $rank_type Whether we should change the reminder ID down or up (options are 'asc' or 'desc')
     * @return  bool
     */
    public static function changeRank($prj_id, $sev_id, $rank_type)
    {
        // check if the current rank is not already the first or last one
        $ranking = self::_getRanking($prj_id);
        $ranks = array_values($ranking);
        $ids = array_keys($ranking);
        $last = end($ids);
        $first = reset($ids);
        if ((($rank_type == 'asc') && ($sev_id == $first)) ||
                (($rank_type == 'desc') && ($sev_id == $last))) {
            return false;
        }

        if ($rank_type == 'asc') {
            $diff = -1;
        } else {
            $diff = 1;
        }
        $new_rank = $ranking[$sev_id] + $diff;
        if (in_array($new_rank, $ranks)) {
            // switch the rankings here...
            $index = array_search($new_rank, $ranks);
            $replaced_sev_id = $ids[$index];
            $sql = 'UPDATE
                        `project_severity`
                     SET
                        sev_rank=?
                     WHERE
                        sev_prj_id=? AND
                        sev_id=?';
            try {
                DB_Helper::getInstance()->query($sql, [$ranking[$sev_id], $prj_id, $replaced_sev_id]);
            } catch (DatabaseException $e) {
                return [];
            }
        }
        $sql = 'UPDATE
                    `project_severity`
                 SET
                    sev_rank=?
                 WHERE
                    sev_prj_id=? AND
                    sev_id=?';
        try {
            DB_Helper::getInstance()->query($sql, [$new_rank, $prj_id, $sev_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return true;
    }

    /**
     * Returns an associative array with the list of severity IDs and
     * their respective ranking.
     *
     * @param   int $prj_id The ID of the project
     * @return  array The list of severities
     */
    private static function _getRanking($prj_id)
    {
        $sql = 'SELECT
                    sev_id,
                    sev_rank
                 FROM
                    `project_severity`
                 WHERE
                    sev_prj_id=?
                 ORDER BY
                    sev_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($sql, [$prj_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the full details of a severity.
     *
     * @param   int $sev_id The severity ID
     * @return  array The information about the severity provided
     */
    public static function getDetails($sev_id)
    {
        $sql = 'SELECT
                    *
                 FROM
                    `project_severity`
                 WHERE
                    sev_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($sql, [$sev_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to remove all severities related to a set of
     * specific projects.
     *
     * @param   array $prj_ids The project IDs to be removed
     * @return  bool Whether the removal worked or not
     */
    public static function removeByProjects($prj_ids)
    {
        $items = DB_Helper::buildList($prj_ids);
        $sql = "DELETE FROM
                    `project_severity`
                 WHERE
                    sev_prj_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($sql, $prj_ids);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove user-selected priorities from the
     * database.
     *
     * @param   array   $sev_ids Severity ids to remove
     * @return  bool Whether the removal worked or not
     */
    public static function remove($sev_ids)
    {
        if (count($sev_ids) < 1) {
            return true;
        }

        $items = DB_Helper::buildList($sev_ids);
        $sql = "DELETE FROM
                    `project_severity`
                 WHERE
                    sev_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($sql, $sev_ids);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update a single severity
     *
     * @return  int 1 if the update worked properly, any other value otherwise
     */
    public static function update($sev_id, $title, $description, $rank)
    {
        if (Validation::isWhitespace($title)) {
            return -2;
        }
        $sql = 'UPDATE
                    `project_severity`
                 SET
                    sev_title=?,
                    sev_description=?,
                    sev_rank=?
                 WHERE
                    sev_id=?';
        try {
            DB_Helper::getInstance()->query($sql, [$title, $description, $rank, $sev_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to add a new severity to the application.
     *
     * @param int $prj_id
     * @return  int 1 if the update worked properly, any other value otherwise
     */
    public static function insert($prj_id, $title, $description, $rank)
    {
        if (Validation::isWhitespace($title)) {
            return -2;
        }
        $sql = 'INSERT INTO
                    `project_severity`
                 SET
                    sev_prj_id = ?,
                    sev_title=?,
                    sev_description=?,
                    sev_rank=?';
        try {
            DB_Helper::getInstance()->query($sql, [$prj_id, $title, $description, $rank]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the full list of severities associated with
     * a specific project.
     *
     * @param   int $prj_id The project ID
     * @return  array The full list of severities
     */
    public static function getList($prj_id)
    {
        $sql = 'SELECT
                    sev_id,
                    sev_title,
                    sev_rank,
                    sev_description
                 FROM
                    `project_severity`
                 WHERE
                    sev_prj_id=?
                 ORDER BY
                    sev_rank ASC';

        try {
            $res = DB_Helper::getInstance()->getAll($sql, [$prj_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res;
    }

    /**
     * Method used to get the title for a severity ID.
     *
     * @param   int $sev_id The severity ID
     * @return  string The severity title
     */
    public static function getTitle($sev_id)
    {
        $sql = 'SELECT
                    sev_title
                 FROM
                    `project_severity`
                 WHERE
                    sev_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$sev_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res;
    }

    /**
     * Method used to get the list of severities as an associative array in the
     * style of (id => title)
     *
     * @param   int $prj_id The project ID
     * @return  array The list of severities
     */
    public static function getAssocList($prj_id)
    {
        static $list;

        if (isset($list[$prj_id])) {
            return $list[$prj_id];
        }

        $sql = 'SELECT
                    sev_id,
                    sev_title
                 FROM
                    `project_severity`
                 WHERE
                    sev_prj_id=?
                 ORDER BY
                    sev_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($sql, [$prj_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        $list[$prj_id] = $res;

        return $res;
    }

    /**
     * Method used to get the sev_id of a project by severity title.
     *
     * @param   int $prj_id The project ID
     * @param   string $sev_title The severity title
     * @return  int $sev_id The severity ID
     */
    public static function getID($prj_id, $sev_title)
    {
        $sql = 'SELECT
                    sev_id
                 FROM
                    `project_severity`
                 WHERE
                    sev_prj_id=?
					AND sev_title = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$prj_id, $sev_title]);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res;
    }
}
