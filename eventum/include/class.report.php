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
// @(#) $Id: s.class.report.php 1.10 04/01/26 20:37:04-06:00 joao@kickass. $
//


/**
 * Class to handle the business logic related to all aspects of the
 * reporting system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.status.php");

class Report
{
    /**
     * Method used to get all open issues and group them by user.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $cutoff_days The number of days to use as a cutoff period
     * @return  array The list of issues
     */
    function getOpenIssuesByUser($prj_id, $cutoff_days)
    {
        $ts = Date_API::getCurrentUnixTimestampGMT();
        $cutoff_ts = $ts - ($cutoff_days * DAY);

        $stmt = "SELECT
                    usr_full_name,
                    iss_id,
                    iss_summary,
                    sta_title,
                    iss_sta_id,
                    iss_created_date,
                    iss_updated_date,
                    iss_last_response_date
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    sta_is_closed=0 AND
                    iss_prj_id=$prj_id AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=usr_id AND
                    UNIX_TIMESTAMP(iss_created_date) < $cutoff_ts
                 ORDER BY
                    usr_full_name";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            Time_Tracking::getTimeSpentByIssues($res);
            $issues = array();
            for ($i = 0; $i < count($res); $i++) {
                if (empty($res[$i]['iss_updated_date'])) {
                    $res[$i]['iss_updated_date'] = $res[$i]['iss_created_date'];
                }
                if (empty($res[$i]['iss_last_response_date'])) {
                    $res[$i]['iss_last_response_date'] = $res[$i]['iss_created_date'];
                }
                $issues[$res[$i]['usr_full_name']][$res[$i]['iss_id']] = array(
                    'iss_summary'         => $res[$i]['iss_summary'],
                    'sta_title'           => $res[$i]['sta_title'],
                    'iss_created_date'    => Date_API::getFormattedDate($res[$i]['iss_created_date']),
                    'time_spent'          => Misc::getFormattedTime($res[$i]['time_spent']),
                    'status_color'        => Status::getStatusColor($res[$i]['iss_sta_id']),
                    'last_update'         => Date_API::getFormattedDateDiff($ts, Date_API::getUnixTimestamp($res[$i]['iss_updated_date'], Date_API::getDefaultTimezone())),
                    'last_email_response' => Date_API::getFormattedDateDiff($ts, Date_API::getUnixTimestamp($res[$i]['iss_last_response_date'], Date_API::getDefaultTimezone()))
                );
            }
            return $issues;
        }
    }


    /**
     * Method used to get the list of issues in a project, and group
     * them by the assignee.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of issues
     */
    function getIssuesByUser($prj_id)
    {
        $stmt = "SELECT
                    usr_full_name,
                    iss_id,
                    iss_summary,
                    sta_title,
                    iss_sta_id,
                    iss_created_date
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    iss_prj_id=$prj_id AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=usr_id
                 ORDER BY
                    usr_full_name";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            Time_Tracking::getTimeSpentByIssues($res);
            $issues = array();
            for ($i = 0; $i < count($res); $i++) {
                $issues[$res[$i]['usr_full_name']][$res[$i]['iss_id']] = array(
                    'iss_summary'      => $res[$i]['iss_summary'],
                    'sta_title'        => $res[$i]['sta_title'],
                    'iss_created_date' => Date_API::getFormattedDate($res[$i]['iss_created_date']),
                    'time_spent'       => Misc::getFormattedTime($res[$i]['time_spent']),
                    'status_color'     => Status::getStatusColor($res[$i]['iss_sta_id'])
                );
            }
            return $issues;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Report Class');
}
?>