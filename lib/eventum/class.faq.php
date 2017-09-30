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

class FAQ
{
    /**
     * Returns the list of FAQ entries associated to a given support level.
     *
     * @param   array $support_level_ids The support level IDs
     * @return  array The list of FAQ entries
     */
    public static function getListBySupportLevel($support_level_ids)
    {
        if (!is_array($support_level_ids)) {
            $support_level_ids = [$support_level_ids];
        }
        $prj_id = Auth::getCurrentProject();

        if (count($support_level_ids) == 0) {
            $stmt = 'SELECT
                        *
                     FROM
                        `faq`
                     WHERE
                        faq_prj_id = ?
                     ORDER BY
                        faq_rank ASC';
            $params = [$prj_id];
        } else {
            $stmt = 'SELECT
                        *
                     FROM
                        `faq`,
                        `faq_support_level`
                     WHERE
                        faq_id=fsl_faq_id AND
                        fsl_support_level_id IN (' . DB_Helper::buildList($support_level_ids) . ') AND
                        faq_prj_id = ?
                     GROUP BY
                        faq_id
                     ORDER BY
                        faq_rank ASC';
            $params = $support_level_ids;
            $params[] = $prj_id;
        }

        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            if (empty($row['faq_updated_date'])) {
                $row['faq_updated_date'] = $row['faq_created_date'];
            }
        }

        return $res;
    }

    /**
     * Method used to remove a FAQ entry from the system.
     *
     * @return  bool
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $stmt = 'DELETE FROM
                    `faq`
                 WHERE
                    faq_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        self::removeSupportLevelAssociations($items);

        return true;
    }

    /**
     * Method used to remove the support level associations for a given
     * FAQ entry.
     *
     * @param   int $faq_id The FAQ ID
     * @return  bool
     */
    public static function removeSupportLevelAssociations($faq_id)
    {
        if (!is_array($faq_id)) {
            $faq_id = [$faq_id];
        }

        $stmt = 'DELETE FROM
                    `faq_support_level`
                 WHERE
                    fsl_faq_id IN (' . DB_Helper::buildList($faq_id) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $faq_id);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update a FAQ entry in the system.
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

        $faq_id = $_POST['id'];
        $stmt = 'UPDATE
                    `faq`
                 SET
                    faq_prj_id=?,
                    faq_updated_date=?,
                    faq_title=?,
                    faq_message=?,
                    faq_rank=?
                 WHERE
                    faq_id=?';
        $params = [$_POST['project'], Date_Helper::getCurrentDateGMT(), $_POST['title'], $_POST['message'], $_POST['rank'], $faq_id];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        // remove all of the associations with support levels, then add them all again
        self::removeSupportLevelAssociations($faq_id);
        if (isset($_POST['support_levels']) && count($_POST['support_levels']) > 0) {
            foreach ($_POST['support_levels'] as $support_level_id) {
                self::addSupportLevelAssociation($faq_id, $support_level_id);
            }
        }

        return 1;
    }

    /**
     * Method used to add a FAQ entry to the system.
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
                    `faq`
                 (
                    faq_prj_id,
                    faq_usr_id,
                    faq_created_date,
                    faq_title,
                    faq_message,
                    faq_rank
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?
                 )';
        $params = [$_POST['project'], Auth::getUserID(), Date_Helper::getCurrentDateGMT(), $_POST['title'], $_POST['message'], $_POST['rank']];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $new_faq_id = DB_Helper::get_last_insert_id();
        if (isset($_POST['support_levels']) && count($_POST['support_levels']) > 0) {
            // now populate the faq-support level mapping table
            foreach ($_POST['support_levels'] as $support_level_id) {
                self::addSupportLevelAssociation($new_faq_id, $support_level_id);
            }
        }

        return 1;
    }

    /**
     * Method used to add a support level association to a FAQ entry.
     *
     * @param   int $faq_id The FAQ ID
     * @param   int $support_level_id The support level ID
     */
    public static function addSupportLevelAssociation($faq_id, $support_level_id)
    {
        $stmt = 'INSERT INTO
                    `faq_support_level`
                 (
                    fsl_faq_id,
                    fsl_support_level_id
                 ) VALUES (
                    ?, ?
                 )';
        DB_Helper::getInstance()->query($stmt, [$faq_id, $support_level_id]);
    }

    /**
     * Method used to get the details of a FAQ entry for a given FAQ ID.
     *
     * @param   int $faq_id The FAQ entry ID
     * @return  array The FAQ entry details
     */
    public static function getDetails($faq_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `faq`
                 WHERE
                    faq_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$faq_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        if ($res == null) {
            return '';
        }
        $res['support_levels'] = array_keys(self::getAssociatedSupportLevels($res['faq_prj_id'], $res['faq_id']));
        if (empty($res['faq_updated_date'])) {
            $res['faq_updated_date'] = $res['faq_created_date'];
        }
        $res['message'] = Misc::activateLinks(nl2br(htmlspecialchars($res['faq_message'])));

        return $res;
    }

    /**
     * Method used to get the list of FAQ entries available in the system.
     *
     * @return  array The list of news entries
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    faq_id,
                    faq_prj_id,
                    faq_title,
                    faq_rank
                 FROM
                    `faq`
                 ORDER BY
                    faq_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        // get the list of associated support levels
        foreach ($res as &$row) {
            $levels = self::getAssociatedSupportLevels($row['faq_prj_id'], $row['faq_id']);
            $row['support_levels'] = implode(', ', array_values($levels));
        }

        return $res;
    }

    /**
     * Method used to get the list of associated support levels for a given
     * FAQ entry.
     *
     * @param   int $prj_id The project ID
     * @param   int $faq_id The FAQ ID
     * @return  array The list of projects
     */
    public static function getAssociatedSupportLevels($prj_id, $faq_id)
    {
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            $stmt = 'SELECT
                        fsl_support_level_id
                     FROM
                        `faq_support_level`
                     WHERE
                        fsl_faq_id=?';
            $ids = DB_Helper::getInstance()->getColumn($stmt, [$faq_id]);

            $t = [];
            $levels = $crm->getSupportLevelAssocList();
            foreach ($levels as $support_level_id => $support_level) {
                if (in_array($support_level_id, $ids)) {
                    $t[$support_level_id] = $support_level;
                }
            }

            return $t;
        }

        return [];
    }

    /**
     * Method used to quickly change the ranking of a faq entry
     * from the administration screen.
     *
     * @param   int $faq_id The faq entry ID
     * @param   string $rank_type Whether we should change the entry down or up (options are 'asc' or 'desc')
     * @return  bool
     */
    public static function changeRank($faq_id, $rank_type)
    {
        // check if the current rank is not already the first or last one
        $ranking = self::_getRanking();
        $ranks = array_values($ranking);
        $ids = array_keys($ranking);
        $last = end($ids);
        $first = reset($ids);
        if ((($rank_type == 'asc') && ($faq_id == $first)) ||
                (($rank_type == 'desc') && ($faq_id == $last))) {
            return false;
        }

        if ($rank_type == 'asc') {
            $diff = -1;
        } else {
            $diff = 1;
        }
        $new_rank = $ranking[$faq_id] + $diff;
        if (in_array($new_rank, $ranks)) {
            // switch the rankings here...
            $index = array_search($new_rank, $ranks);
            $replaced_faq_id = $ids[$index];
            $stmt = 'UPDATE
                        `faq`
                     SET
                        faq_rank=?
                     WHERE
                        faq_id=?';
            DB_Helper::getInstance()->query($stmt, [$ranking[$faq_id], $replaced_faq_id]);
        }
        $stmt = 'UPDATE
                    `faq`
                 SET
                    faq_rank=?
                 WHERE
                    faq_id=?';
        DB_Helper::getInstance()->query($stmt, [$new_rank, $faq_id]);

        return true;
    }

    /**
     * Returns an associative array with the list of faq entry
     * IDs and their respective ranking.
     *
     * @return  array The list of faq entries
     */
    private function _getRanking()
    {
        $stmt = 'SELECT
                    faq_id,
                    faq_rank
                 FROM
                    `faq`
                 ORDER BY
                    faq_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }
}
