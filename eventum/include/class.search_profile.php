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
// @(#) $Id: $
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");


class Search_Profile
{
    /**
     * Method used to remove the search profile record for this user, 
     * for the specified project and profile type.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @return  boolean
     */
    function remove($usr_id, $prj_id, $type)
    {
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_profile
                 WHERE
                    sep_usr_id=" . Misc::escapeInteger($usr_id) . " AND
                    sep_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    sep_type='" . Misc::escapeString($type) . "'";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to retrieve a search profile record for this user, 
     * for the specified project and profile type.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @return  array The user's search profile
     */
    function getProfile($usr_id, $prj_id, $type)
    {
        static $returns;

        if (!empty($returns[$usr_id][$prj_id][$type])) {
            return $returns[$usr_id][$prj_id][$type];
        }

        $stmt = "SELECT
                    sep_user_profile
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_profile
                 WHERE
                    sep_usr_id=" . Misc::escapeInteger($usr_id) . " AND
                    sep_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    sep_type='" . Misc::escapeString($type) . "'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (empty($res)) {
                return array();
            } else {
                $returns[$usr_id][$prj_id][$type] = unserialize($res);
                return unserialize($res);
            }
        }
    }


    /**
     * Method used to check whether a search profile already exists
     * or not.
     *
     * @access  private
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @return  boolean
     */
    function _exists($usr_id, $prj_id, $type)
    {
        $stmt = "SELECT
                    COUNT(*) AS total
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_profile
                 WHERE
                    sep_usr_id=" . Misc::escapeInteger($usr_id) . " AND
                    sep_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    sep_type='" . Misc::escapeString($type) . "'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($res > 0) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * Method used to save a search profile record for this user, for 
     * the specified project, and profile type.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  boolean
     */
    function save($usr_id, $prj_id, $type, $profile)
    {
        if (!Search_Profile::_exists($usr_id, $prj_id, $type)) {
            return Search_Profile::_insert($usr_id, $prj_id, $type, $profile);
        } else {
            return Search_Profile::_update($usr_id, $prj_id, $type, $profile);
        }
    }


    /**
     * Method used to create a new search profile record for this 
     * user, for the specified project, and profile type.
     *
     * @access  private
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  boolean
     */
    function _insert($usr_id, $prj_id, $type, $profile)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_profile
                 (
                    sep_usr_id,
                    sep_prj_id,
                    sep_type,
                    sep_user_profile
                 ) VALUES (
                    " . Misc::escapeInteger($usr_id) . ",
                    " . Misc::escapeInteger($prj_id) . ",
                    '" . Misc::escapeString($type) . "',
                    '" . Misc::escapeString(serialize($profile)) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update an existing search profile record for 
     * this user, for the specified project, and profile type.
     *
     * @access  private
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @param   string $type The type of the search profile ('issue' or 'email')
     * @param   string $profile The search profile to be saved
     * @return  boolean
     */
    function _update($usr_id, $prj_id, $type, $profile)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "search_profile
                 SET
                    sep_user_profile='" . Misc::escapeString(serialize($profile)) . "'
                 WHERE
                    sep_usr_id=" . Misc::escapeInteger($usr_id) . " AND
                    sep_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    sep_type='" . Misc::escapeString($type) . "'";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Search_Profile Class');
}
?>