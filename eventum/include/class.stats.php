<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// @(#) $Id: s.class.stats.php 1.19 04/01/16 23:17:54-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the generation of the 
 * issue statistics displayed in the main screen of the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.release.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.status.php");

class Stats
{
    /**
     * Method used to check if the provided array has valid data (i.e. non-zero)
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
        if (!@file_exists(APP_JPGRAPH_PATH)) {
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
     * @return  array List of categories
     */
    function getAssocCategory()
    {
        $prj_id = Auth::getCurrentProject();
        $list = Category::getAssocList($prj_id);
        $stats = array();
        foreach ($list as $prc_id => $prc_title) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     WHERE
                        iss_prj_id=$prj_id AND
                        iss_prc_id=" . $prc_id;
            $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
            $stats[$prc_title] = $res;
        }
        return $stats;
    }


    /**
     * Method used to get an associative array of the list of releases and the
     * total number of issues associated with each of them.
     *
     * @access  public
     * @return  array List of releases
     */
    function getAssocRelease()
    {
        $prj_id = Auth::getCurrentProject();
        $list = Release::getAssocList($prj_id);
        $stats = array();
        foreach ($list as $pre_id => $pre_title) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     WHERE
                        iss_prj_id=$prj_id AND
                        iss_pre_id=" . $pre_id;
            $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
            $stats[$pre_title] = $res;
        }
        return $stats;
    }


    /**
     * Method used to get an associative array of the list of statuses and the
     * total number of issues associated with each of them.
     *
     * @access  public
     * @return  array List of statuses
     */
    function getAssocStatus()
    {
        $prj_id = Auth::getCurrentProject();
        $list = Status::getAssocStatusList($prj_id);
        $stats = array();
        foreach ($list as $sta_id => $sta_title) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     WHERE
                        iss_prj_id=$prj_id AND
                        iss_sta_id=" . $sta_id;
            $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
            $stats[$sta_title] = $res;
        }
        return $stats;
    }


    /**
     * Method used to get the list of statuses and the total number of issues
     * associated with each of them.
     *
     * @access  public
     * @return  array List of statuses
     */
    function getStatus()
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
                    iss_prj_id=$prj_id
                 GROUP BY
                    iss_sta_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
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
     * @return  array List of categories
     */
    function getCategory()
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT iss_prc_id,
                    prc_title,
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category
                 WHERE
                    iss_prj_id=$prj_id AND
                    iss_prc_id=prc_id
                 GROUP BY
                    iss_prc_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
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
     * @return  array List of releases
     */
    function getRelease()
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT iss_pre_id,
                    pre_title,
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 WHERE
                    iss_prj_id=$prj_id AND
                    iss_pre_id=pre_id
                 GROUP BY
                    iss_pre_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
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
     * @return  array List of priorities
     */
    function getAssocPriority()
    {
        $prj_id = Auth::getCurrentProject();
        $list = Misc::getAssocPriorities();
        $stats = array();
        foreach ($list as $pri_id => $pri_title) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     WHERE
                        iss_prj_id=$prj_id AND
                        iss_pri_id=" . $pri_id;
            $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
            $stats[$pri_title] = $res;
        }
        return $stats;
    }


    /**
     * Method used to get the list of priorities and the total number of issues
     * associated with each of them.
     *
     * @access  public
     * @return  array List of statuses
     */
    function getPriority()
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT iss_pri_id,
                    pri_title,
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "priority
                 WHERE
                    iss_pri_id=pri_id AND
                    iss_prj_id=$prj_id
                 GROUP BY
                    iss_pri_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
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
     * @return  array List of users
     */
    function getAssocUser()
    {
        $prj_id = Auth::getCurrentProject();
        $list = Project::getUserAssocList($prj_id, 'stats', User::getRoleID('Reporter'));
        $stats = array();
        foreach ($list as $usr_id => $usr_full_name) {
            $stmt = "SELECT
                        COUNT(*) AS total_items
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                     WHERE
                        isu_iss_id=iss_id AND
                        iss_prj_id=$prj_id AND
                        isu_usr_id=" . $usr_id;
            $res = (integer) $GLOBALS["db_api"]->dbh->getOne($stmt);
            if ($res > 0) {
                $stats[$usr_full_name] = $res;
            }
        }
        return $stats;
    }


    /**
     * Method used to get the list of users and the total number of issues
     * associated with each of them.
     *
     * @access  public
     * @return  array List of users
     */
    function getUser()
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    DISTINCT isu_usr_id,
                    usr_full_name,
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    isu_usr_id=usr_id AND
                    isu_iss_id=iss_id AND
                    iss_prj_id=$prj_id
                 GROUP BY
                    isu_usr_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
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
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=$prj_id AND
                    sup_iss_id=0 AND
                    sup_removed=0";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        $stmt = "SELECT
                    COUNT(*) AS total_items
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=$prj_id AND
                    sup_iss_id > 0 AND
                    sup_removed=0";
        $res2 = $GLOBALS["db_api"]->dbh->getOne($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res2)) {
            Error_Handler::logError(array($res2->getMessage(), $res2->getDebugInfo()), __FILE__, __LINE__);
            return "";
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
        $res3 = $GLOBALS["db_api"]->dbh->getOne($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res3)) {
            Error_Handler::logError(array($res3->getMessage(), $res3->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return array(
            "pending"    => $res,
            "associated" => $res2,
            "removed"    => $res3
        );
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Stats Class');
}
?>