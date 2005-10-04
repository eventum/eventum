<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
//
// @(#) $Id$
//


class FAQ
{
    /**
     * Returns the list of FAQ entries associated to a given support level.
     *
     * @access  public
     * @param   integer $support_level_id The support level ID
     * @return  array The list of FAQ entries
     */
    function getListBySupportLevel($support_level_id)
    {
        $support_level_id = Misc::escapeInteger($support_level_id);
        $prj_id = Auth::getCurrentProject();

        if ($support_level_id == -1) {
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
                        fsl_support_level_id=$support_level_id AND
                        faq_prj_id = $prj_id
                     ORDER BY
                        faq_rank ASC";
        }
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                if (empty($res[$i]['faq_updated_date'])) {
                    $res[$i]['faq_updated_date'] = $res[$i]['faq_created_date'];
                }
                $res[$i]['faq_updated_date'] = Date_API::getSimpleDate($res[$i]["faq_updated_date"]);
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
        global $HTTP_POST_VARS;

        $items = @implode(", ", Misc::escapeInteger($HTTP_POST_VARS["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 WHERE
                    faq_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            FAQ::removeSupportLevelAssociations($HTTP_POST_VARS['items']);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        global $HTTP_POST_VARS;

        $HTTP_POST_VARS['id'] = Misc::escapeInteger($HTTP_POST_VARS['id']);
        
        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        if (Validation::isWhitespace($HTTP_POST_VARS["message"])) {
            return -3;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 SET
                    faq_prj_id=" . $HTTP_POST_VARS['project'] . ",
                    faq_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    faq_title='" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                    faq_message='" . Misc::escapeString($HTTP_POST_VARS["message"]) . "',
                    faq_rank=" . $HTTP_POST_VARS['rank'] . "
                 WHERE
                    faq_id=" . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // remove all of the associations with support levels, then add them all again
            FAQ::removeSupportLevelAssociations($HTTP_POST_VARS['id']);
            if (Customer::doesBackendUseSupportLevels($HTTP_POST_VARS['project'])) {
                foreach ($HTTP_POST_VARS['support_levels'] as $support_level_id) {
                    FAQ::addSupportLevelAssociation($HTTP_POST_VARS['id'], $support_level_id);
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
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        if (Validation::isWhitespace($HTTP_POST_VARS["message"])) {
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
                    " . $HTTP_POST_VARS['project'] . ",
                    " . Auth::getUserID() . ",
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["message"]) . "',
                    " . $HTTP_POST_VARS['rank'] . "
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_faq_id = $GLOBALS["db_api"]->get_last_insert_id();
            if (Customer::doesBackendUseSupportLevels(Misc::escapeInteger($HTTP_POST_VARS['project']))) {
                // now populate the faq-support level mapping table
                foreach ($HTTP_POST_VARS['support_levels'] as $support_level_id) {
                    FAQ::addSupportLevelAssociation($new_faq_id, $support_level_id);
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
                    " . Misc::escapeInteger($support_level_id) . "
                 )";
        $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (Customer::doesBackendUseSupportLevels($res['faq_prj_id'])) {
                // get all of the support level associations here as well
                $res['support_levels'] = array_keys(FAQ::getAssociatedSupportLevels($res['faq_prj_id'], $res['faq_id']));
            }
            if (empty($res['faq_updated_date'])) {
                $res['faq_updated_date'] = $res['faq_created_date'];
            }
            $res['faq_updated_date'] = Date_API::getFormattedDate($res['faq_updated_date']);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get the list of associated support levels
            for ($i = 0; $i < count($res); $i++) {
                if (Customer::doesBackendUseSupportLevels($res[$i]['faq_prj_id'])) {
                    $res[$i]['support_levels'] = implode(", ", array_values(FAQ::getAssociatedSupportLevels($res[$i]['faq_prj_id'], $res[$i]['faq_id'])));
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
        $ids = $GLOBALS["db_api"]->dbh->getCol($stmt);

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
        $ranking = FAQ::_getRanking();
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
            $GLOBALS["db_api"]->dbh->query($stmt);
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "faq
                 SET
                    faq_rank=" . $new_rank . "
                 WHERE
                    faq_id=" . $faq_id;
        $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included FAQ Class');
}
?>