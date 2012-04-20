<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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


class News
{
    /**
     * Method used to get the list of news entries available in the
     * system for a given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of news entries
     */
    function getListByProject($prj_id, $show_full_message = FALSE)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "news,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_news
                 WHERE
                    prn_nws_id=nws_id AND
                    prn_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    nws_status='active'
                 ORDER BY
                    nws_created_date DESC
                 LIMIT
                    0, 3";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['nws_created_date'] = Date_Helper::getSimpleDate($res[$i]["nws_created_date"]);
                if ((!$show_full_message) && (strlen($res[$i]['nws_message']) > 300)) {
                    $next_space = strpos($res[$i]['nws_message'], ' ', 254);
                    if (empty($next_space)) {
                        $next_space = strpos($res[$i]['nws_message'], "\n", 254);
                    }
                    if (($next_space > 0) && (($next_space - 255) < 50)) {
                        $cut = $next_space;
                    } else {
                        $cut = 255;
                    }
                    $res[$i]['nws_message'] = substr($res[$i]['nws_message'], 0, $cut) . '...';
                }
                $res[$i]['nws_message'] = nl2br(htmlspecialchars($res[$i]['nws_message']));
            }
            return $res;
        }
    }


    /**
     * Method used to add a project association to a news entry.
     *
     * @access  public
     * @param   integer $nws_id The news ID
     * @param   integer $prj_id The project ID
     * @return  void
     */
    function addProjectAssociation($nws_id, $prj_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_news
                 (
                    prn_nws_id,
                    prn_prj_id
                 ) VALUES (
                    " . Misc::escapeInteger($nws_id) . ",
                    " . Misc::escapeInteger($prj_id) . "
                 )";
        DB_Helper::getInstance()->query($stmt);
    }


    /**
     * Method used to add a news entry to the system.
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "news
                 (
                    nws_usr_id,
                    nws_created_date,
                    nws_title,
                    nws_message,
                    nws_status
                 ) VALUES (
                    " . Auth::getUserID() . ",
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($_POST["title"]) . "',
                    '" . Misc::escapeString($_POST["message"]) . "',
                    '" . Misc::escapeString($_POST["status"]) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_news_id = DB_Helper::get_last_insert_id();
            // now populate the project-news mapping table
            foreach ($_POST['projects'] as $prj_id) {
                self::addProjectAssociation($new_news_id, $prj_id);
            }
            return 1;
        }
    }


    /**
     * Method used to remove a news entry from the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "news
                 WHERE
                    nws_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            self::removeProjectAssociations($_POST['items']);
            return true;
        }
    }


    /**
     * Method used to remove the project associations for a given
     * news entry.
     *
     * @access  public
     * @param   integer $nws_id The news ID
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function removeProjectAssociations($nws_id, $prj_id=FALSE)
    {
        if (!is_array($nws_id)) {
            $nws_id = array($nws_id);
        }
        $items = @implode(", ", Misc::escapeInteger($nws_id));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_news
                 WHERE
                    prn_nws_id IN ($items)";
        if ($prj_id) {
            $stmt .= " AND prn_prj_id=" . Misc::escapeInteger($prj_id);
        }
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update a news entry in the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        if (Validation::isWhitespace($_POST["message"])) {
            return -3;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "news
                 SET
                    nws_title='" . Misc::escapeString($_POST["title"]) . "',
                    nws_message='" . Misc::escapeString($_POST["message"]) . "',
                    nws_status='" . Misc::escapeString($_POST["status"]) . "'
                 WHERE
                    nws_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // remove all of the associations with projects, then add them all again
            self::removeProjectAssociations($_POST['id']);
            foreach ($_POST['projects'] as $prj_id) {
                self::addProjectAssociation($_POST['id'], $prj_id);
            }
            return 1;
        }
    }


    /**
     * Method used to get the details of a news entry for a given news ID.
     *
     * @access  public
     * @param   integer $nws_id The news entry ID
     * @return  array The news entry details
     */
    function getDetails($nws_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "news
                 WHERE
                    nws_id=" . Misc::escapeInteger($nws_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get all of the project associations here as well
            $res['projects'] = array_keys(self::getAssociatedProjects($res['nws_id']));
            $res['nws_message'] = nl2br(htmlspecialchars($res['nws_message']));
            return $res;
        }
    }


    /**
     * Method used to get the details of a news entry for a given news ID.
     *
     * @access  public
     * @param   integer $nws_id The news entry ID
     * @return  array The news entry details
     */
    function getAdminDetails($nws_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "news
                 WHERE
                    nws_id=" . Misc::escapeInteger($nws_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get all of the project associations here as well
            $res['projects'] = array_keys(self::getAssociatedProjects($res['nws_id']));
            return $res;
        }
    }


    /**
     * Method used to get the list of news entries available in the system.
     *
     * @access  public
     * @return  array The list of news entries
     */
    function getList()
    {
        $stmt = "SELECT
                    nws_id,
                    nws_title,
                    nws_status
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "news
                 ORDER BY
                    nws_title ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get the list of associated projects
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['projects'] = implode(", ", array_values(self::getAssociatedProjects($res[$i]['nws_id'])));
            }
            return $res;
        }
    }


    /**
     * Method used to get the list of associated projects for a given
     * news entry.
     *
     * @access  public
     * @param   integer $nws_id The news ID
     * @return  array The list of projects
     */
    function getAssociatedProjects($nws_id)
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_news
                 WHERE
                    prj_id=prn_prj_id AND
                    prn_nws_id=" . Misc::escapeInteger($nws_id);
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }
}
