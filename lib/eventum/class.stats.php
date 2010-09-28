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


/**
 * Class to handle the business logic related to the generation of the
 * issue statistics displayed in the main screen of the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Stats
{
    /**
     * Method used to check if the provided array has valid data (e.g. non-zero)
     *
     * @access  public
     * @param   array $data The data to check against
     * @return  boolean
     */
    function hasData($data)
    {
        foreach ($data as $piece) {
            if ($piece) {
                return true;
            }
        }
        return false;
    }


    /**
     * Method used to check if the pie charts should be displayed in the main
     * screen of the application.
     *
     * @access  public
     * @return  boolean
     */
    function getPieChart()
    {
        if (!file_exists(APP_JPGRAPH_PATH)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to get an associative array of the list of categories and the
     * total number of issues associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of categories
     */
    function getAssocCategory($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Category::getAssocList($prj_id);
        $stats = array();
        foreach ($list as $prc_id => $prc_title) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                     WHERE
                        iss_sta_id = sta_id AND
                        iss_prj_id=$prj_id AND
                        iss_prc_id=" . $prc_id;
            if ($hide_closed) {
                $stmt .= " AND
                        sta_is_closed = 0";
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt);
            if ($res > 0) {
                $stats[$prc_title] = $res;
            }
        }
        arsort($stats);
        return $stats;
    }


    /**
     * Method used to get an associative array of the list of releases and the
     * total number of issues associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of releases
     */
    function getAssocRelease($hide_closed = true)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Release::getAssocList($prj_id);
        $stats = array();
        foreach ($list as $pre_id => $pre_title) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                     WHERE
                        iss_sta_id = sta_id AND
                        iss_prj_id=$prj_id AND
                        iss_pre_id=" . $pre_id;
            if ($hide_closed) {
                $stmt .= " AND
                        sta_is_closed = 0";
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt);
            if ($res > 0) {
                $stats[$pre_title] = $res;
            }
        }
        arsort($stats);
        return $stats;
    }


    /**
     * Method used to get an associative array of the list of statuses and the
     * total number of issues associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of statuses
     */
    function getAssocStatus($hide_closed = true)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Status::getAssocStatusList($prj_id);
        $stats = array();
        foreach ($list as $sta_id => $sta_title) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                     WHERE
                        iss_sta_id = sta_id AND
                        iss_prj_id=$prj_id AND
                        iss_sta_id=" . $sta_id;
            if ($hide_closed) {
                $stmt .= " AND
                        sta_is_closed = 0";
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt);
            if ($res > 0) {
                $stats[$sta_title] = $res;
            }
        }
        arsort($stats);
        return $stats;
    }


    /**
     * Method used to get the list of statuses and the total number of issues
     * associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of statuses
     */
    function getStatus($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT iss_sta_id,
                    sta_title,
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    iss_sta_id=sta_id AND
                    iss_prj_id=$prj_id";
            if ($hide_closed) {
                $stmt .= " AND
                        sta_is_closed = 0";
            }
            $stmt .= "
                 GROUP BY
                    iss_sta_id
                 ORDER BY
                    total_items DESC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of categories and the total number of issues
     * associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of categories
     */
    function getCategory($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT iss_prc_id,
                    prc_title,
                    SUM(IF(sta_is_closed=0, 1, 0)) AS total_open_items,
                    SUM(IF(sta_is_closed=1, 1, 0)) AS total_closed_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    iss_prj_id=$prj_id AND
                    iss_prc_id=prc_id AND
                    iss_sta_id=sta_id";
        if ($hide_closed) {
            $stmt .= " AND
                    sta_is_closed = 0";
        }
        $stmt .= "
                 GROUP BY
                    iss_prc_id
                 ORDER BY
                    total_open_items";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of releases and the total number of issues
     * associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of releases
     */
    function getRelease($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT iss_pre_id,
                    pre_title,
                    SUM(IF(sta_is_closed=0, 1, 0)) AS total_open_items,
                    SUM(IF(sta_is_closed=1, 1, 0)) AS total_closed_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    iss_prj_id=$prj_id AND
                    iss_pre_id=pre_id AND
                    iss_sta_id=sta_id";
        if ($hide_closed) {
            $stmt .= " AND
                    sta_is_closed = 0";
        }
        $stmt .= "
                 GROUP BY
                    iss_pre_id
                 ORDER BY
                    total_open_items DESC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of the list of priorities and the
     * total number of issues associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of priorities
     */
    function getAssocPriority($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Priority::getAssocList($prj_id);
        $stats = array();
        foreach ($list as $pri_id => $pri_title) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                     WHERE
                        iss_sta_id = sta_id AND
                        iss_prj_id=$prj_id AND
                        iss_pri_id=" . $pri_id;
            if ($hide_closed) {
                $stmt .= " AND
                        sta_is_closed = 0";
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt);
            if ($res > 0) {
                $stats[$pri_title] = $res;
            }
        }
        arsort($stats);
        return $stats;
    }


    /**
     * Method used to get the list of priorities and the total number of issues
     * associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of statuses
     */
    function getPriority($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT iss_pri_id,
                    pri_title,
                    SUM(IF(sta_is_closed=0, 1, 0)) AS total_open_items,
                    SUM(IF(sta_is_closed=1, 1, 0)) AS total_closed_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    iss_pri_id=pri_id AND
                    iss_sta_id=sta_id AND
                    iss_prj_id=$prj_id";
        if ($hide_closed) {
            $stmt .= " AND
                    sta_is_closed = 0";
        }
        $stmt .= "
                 GROUP BY
                    iss_pri_id
                 ORDER BY
                    total_open_items DESC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of the list of users and the
     * total number of issues associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of users
     */
    function getAssocUser($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Project::getUserAssocList($prj_id, 'stats', User::getRoleID('Customer'));
        $stats = array();
        foreach ($list as $usr_id => $usr_full_name) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                     WHERE
                        iss_sta_id = sta_id AND
                        isu_iss_id=iss_id AND
                        iss_prj_id=$prj_id AND
                        isu_usr_id=" . $usr_id;
            if ($hide_closed) {
                $stmt .= " AND
                        sta_is_closed = 0";
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt);
            if ($res > 0) {
                $stats[$usr_full_name] = $res;
            }
        }
        arsort($stats);
        return $stats;
    }


    /**
     * Method used to get the list of users and the total number of issues
     * associated with each of them.
     *
     * @access  public
     * @param   boolean $hide_closed If closed issues should be hidden.
     * @return  array List of users
     */
    function getUser($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT isu_usr_id,
                    usr_full_name,
                    SUM(IF(sta_is_closed=0, 1, 0)) AS total_open_items,
                    SUM(IF(sta_is_closed=1, 1, 0)) AS total_closed_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    isu_usr_id=usr_id AND
                    isu_iss_id=iss_id AND
                    iss_prj_id=$prj_id AND
                    iss_sta_id=sta_id";
        if ($hide_closed) {
            $stmt .= " AND
                    sta_is_closed = 0";
        }
        $stmt .= "
                 GROUP BY
                    isu_usr_id
                 ORDER BY
                    total_open_items DESC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the total number of issues associated with each
     * email status.
     *
     * @access  public
     * @return  array List of statuses
     */
    function getEmailStatus()
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    IF(sup_iss_id > 0, 'associated', 'unassociated') type,
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=$prj_id AND
                    sup_removed=0
                 GROUP BY
                    type";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        if (empty($res['associated'])) {
            $res['associated'] = 0;
        }
        if (empty($res['unassociated'])) {
            $res['unassociated'] = 0;
        }
        $stmt = "SELECT
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=$prj_id AND
                    sup_removed=1";
        $res3 = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res3)) {
            Error_Handler::logError(array($res3->getMessage(), $res3->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return array(
            "pending"    => $res['unassociated'],
            "associated" => $res['associated'],
            "removed"    => $res3
        );
    }
}
