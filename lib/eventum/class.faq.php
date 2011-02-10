<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+


class FAQ
{
    /**
     * Returns the list of FAQ entries associated to a given support level.
     *
     * @access  public
     * @param   array $support_level_ids The support level IDs
     * @return  array The list of FAQ entries
     */
    function getListBySupportLevel($support_level_ids)
    {
        if (!is_array($support_level_ids)) {
            $support_level_ids = array($support_level_ids);
        }
        $support_level_ids = Misc::escapeString($support_level_ids);
        $prj_id = Auth::getCurrentProject();

        if (count($support_level_ids) == 0) {
            $stmt = "SELECT
                        *
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                     WHERE
                        faq_prj_id = $prj_id
                     ORDER BY
                        faq_rank ASC";
        } else {
            $stmt = "SELECT
                        *
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq_support_level
                     WHERE
                        faq_id=fsl_faq_id AND
                        fsl_support_level_id IN('" . join("', '", $support_level_ids) . "') AND
                        faq_prj_id = $prj_id
                     GROUP BY
                        faq_id
                     ORDER BY
                        faq_rank ASC";
        }
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                if (empty($res[$i]['faq_updated_date'])) {
                    $res[$i]['faq_updated_date'] = $res[$i]['faq_created_date'];
                }
                $res[$i]['faq_updated_date'] = Date_Helper::getSimpleDate($res[$i]["faq_updated_date"]);
            }
            return $res;
        }
    }


    /**
     * Method used to remove a FAQ entry from the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 WHERE
                    faq_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            self::removeSupportLevelAssociations($_POST['items']);
            return true;
        }
    }


    /**
     * Method used to remove the support level associations for a given
     * FAQ entry.
     *
     * @access  public
     * @param   integer $faq_id The FAQ ID
     * @return  boolean
     */
    function removeSupportLevelAssociations($faq_id)
    {
        $faq_id = Misc::escapeInteger($faq_id);
        if (!is_array($faq_id)) {
            $faq_id = array($faq_id);
        }
        $items = @implode(", ", $faq_id);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq_support_level
                 WHERE
                    fsl_faq_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update a FAQ entry in the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
    {
        $_POST['id'] = Misc::escapeInteger($_POST['id']);

        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        if (Validation::isWhitespace($_POST["message"])) {
            return -3;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 SET
                    faq_prj_id=" . $_POST['project'] . ",
                    faq_updated_date='" . Date_Helper::getCurrentDateGMT() . "',
                    faq_title='" . Misc::escapeString($_POST["title"]) . "',
                    faq_message='" . Misc::escapeString($_POST["message"]) . "',
                    faq_rank=" . $_POST['rank'] . "
                 WHERE
                    faq_id=" . $_POST["id"];
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // remove all of the associations with support levels, then add them all again
            self::removeSupportLevelAssociations($_POST['id']);
            if (Customer::doesBackendUseSupportLevels($_POST['project'])) {
                foreach ($_POST['support_levels'] as $support_level_id) {
                    self::addSupportLevelAssociation($_POST['id'], $support_level_id);
                }
            }
            return 1;
        }
    }


    /**
     * Method used to add a FAQ entry to the system.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function insert()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        if (Validation::isWhitespace($_POST["message"])) {
            return -3;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 (
                    faq_prj_id,
                    faq_usr_id,
                    faq_created_date,
                    faq_title,
                    faq_message,
                    faq_rank
                 ) VALUES (
                    " . $_POST['project'] . ",
                    " . Auth::getUserID() . ",
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($_POST["title"]) . "',
                    '" . Misc::escapeString($_POST["message"]) . "',
                    " . $_POST['rank'] . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_faq_id = DB_Helper::get_last_insert_id();
            if (Customer::doesBackendUseSupportLevels(Misc::escapeInteger($_POST['project']))) {
                // now populate the faq-support level mapping table
                foreach ($_POST['support_levels'] as $support_level_id) {
                    self::addSupportLevelAssociation($new_faq_id, $support_level_id);
                }
            }
            return 1;
        }
    }


    /**
     * Method used to add a support level association to a FAQ entry.
     *
     * @access  public
     * @param   integer $faq_id The FAQ ID
     * @param   integer $support_level_id The support level ID
     * @return  void
     */
    function addSupportLevelAssociation($faq_id, $support_level_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq_support_level
                 (
                    fsl_faq_id,
                    fsl_support_level_id
                 ) VALUES (
                    " . Misc::escapeInteger($faq_id) . ",
                    '" . Misc::escapeString($support_level_id) . "'
                 )";
        DB_Helper::getInstance()->query($stmt);
    }


    /**
     * Method used to get the details of a FAQ entry for a given FAQ ID.
     *
     * @access  public
     * @param   integer $faq_id The FAQ entry ID
     * @return  array The FAQ entry details
     */
    function getDetails($faq_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 WHERE
                    faq_id=" . Misc::escapeInteger($faq_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if ($res == NULL) {
                return "";
            }
            if (Customer::doesBackendUseSupportLevels($res['faq_prj_id'])) {
                // get all of the support level associations here as well
                $res['support_levels'] = array_keys(self::getAssociatedSupportLevels($res['faq_prj_id'], $res['faq_id']));
            }
            if (empty($res['faq_updated_date'])) {
                $res['faq_updated_date'] = $res['faq_created_date'];
            }
            $res['faq_updated_date'] = Date_Helper::getFormattedDate($res['faq_updated_date']);
            $res['message'] = Misc::activateLinks(nl2br(htmlspecialchars($res['faq_message'])));
            return $res;
        }
    }


    /**
     * Method used to get the list of FAQ entries available in the system.
     *
     * @access  public
     * @return  array The list of news entries
     */
    function getList()
    {
        $stmt = "SELECT
                    faq_id,
                    faq_prj_id,
                    faq_title,
                    faq_rank
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 ORDER BY
                    faq_rank ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get the list of associated support levels
            for ($i = 0; $i < count($res); $i++) {
                if (Customer::doesBackendUseSupportLevels($res[$i]['faq_prj_id'])) {
                    $res[$i]['support_levels'] = implode(", ", array_values(self::getAssociatedSupportLevels($res[$i]['faq_prj_id'], $res[$i]['faq_id'])));
                }
            }
            return $res;
        }
    }


    /**
     * Method used to get the list of associated support levels for a given
     * FAQ entry.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $faq_id The FAQ ID
     * @return  array The list of projects
     */
    function getAssociatedSupportLevels($prj_id, $faq_id)
    {
        $stmt = "SELECT
                    fsl_support_level_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq_support_level
                 WHERE
                    fsl_faq_id=" . Misc::escapeInteger($faq_id);
        $ids = DB_Helper::getInstance()->getCol($stmt);

        $t = array();
        $levels = Customer::getSupportLevelAssocList(Misc::escapeInteger($prj_id));
        foreach ($levels as $support_level_id => $support_level) {
            if (in_array($support_level_id, $ids)) {
                $t[$support_level_id] = $support_level;
            }
        }
        return $t;
    }


    /**
     * Method used to quickly change the ranking of a faq entry
     * from the administration screen.
     *
     * @access  public
     * @param   integer $faq_id The faq entry ID
     * @param   string $rank_type Whether we should change the entry down or up (options are 'asc' or 'desc')
     * @return  boolean
     */
    function changeRank($faq_id, $rank_type)
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
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                     SET
                        faq_rank=" . $ranking[$faq_id] . "
                     WHERE
                        faq_id=" . $replaced_faq_id;
            DB_Helper::getInstance()->query($stmt);
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 SET
                    faq_rank=" . $new_rank . "
                 WHERE
                    faq_id=" . $faq_id;
        DB_Helper::getInstance()->query($stmt);
        return true;
    }


    /**
     * Returns an associative array with the list of faq entry
     * IDs and their respective ranking.
     *
     * @access  private
     * @return  array The list of faq entries
     */
    function _getRanking()
    {
        $stmt = "SELECT
                    faq_id,
                    faq_rank
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 ORDER BY
                    faq_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }
}
