<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

// This report shows a list of activity performed in recent history.
$tpl = new Template_Helper();
$tpl->setTemplate("reports/recent_activity.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (!Access::canAccessReports(Auth::getUserID())) {
    echo "Invalid role";
    exit;
}

$units = array(
    "hour"  =>  "Hours",
    "day"   =>  "Days"
);
$type_list = array(
    "phone" =>  "Phone Calls",
    "note"  =>  "Notes",
    "email" =>  "Email",
    "draft" =>  "Drafts",
    "time"  =>  "Time Tracking",
    "reminder"  =>  "Reminders"
);

if (empty($_REQUEST['activity_types'])) {
    $_REQUEST['activity_types'] = array_keys($type_list);
}

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();

$tpl->assign(array(
    "units" =>  $units,
    "users" => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
    "developer" => $usr_id,
    "type_list" =>  $type_list,
    "activity_types"    =>  $_REQUEST['activity_types']
));


if (((!empty($_REQUEST['unit'])) && (!empty($_REQUEST['amount']))) || (@count($_REQUEST['start']) == 3)) {

    if (count(@$_REQUEST["start"]) > 0 &&
            (@$_REQUEST["start"]["Year"] != 0) &&
            (@$_REQUEST["start"]["Month"] != 0) &&
            (@$_REQUEST["start"]["Day"] != 0)) {
        $start_date = join("-", $_POST["start"]);
    }
    if (count(@$_REQUEST["end"]) > 0 &&
            (@$_REQUEST["end"]["Year"] != 0) &&
            (@$_REQUEST["end"]["Month"] != 0) &&
            (@$_REQUEST["end"]["Day"] != 0)) {
        $end_date = join("-", $_POST["end"]);
    }

    $data = array();
    if (in_array('phone', $_REQUEST['activity_types'])) {
        $sql = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support.*,
                    phc_title,
                    usr_full_name,
                    iss_summary,
                    sta_color
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                WHERE
                    iss_sta_id = sta_id AND
                    phs_phc_id = phc_id AND
                    phs_iss_id = iss_id AND
                    phs_usr_id = usr_id AND
                    iss_prj_id = $prj_id AND\n";
        $sql .= createWhereClause('phs_created_date', 'usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            $data['phone'] = processResult($res, 'phs_created_date', 'phs_iss_id');
        }
    }

    if (in_array('note', $_REQUEST['activity_types'])) {
        $sql = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note.*,
                    usr_full_name,
                    iss_summary,
                    sta_color
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                WHERE
                    iss_sta_id = sta_id AND
                    not_iss_id = iss_id AND
                    not_usr_id = usr_id AND
                    iss_prj_id = $prj_id AND\n";
        $sql .= createWhereClause('not_created_date', 'not_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            $data['note'] = processResult($res, 'not_created_date', 'not_iss_id');
        }
    }

    if (in_array('email', $_REQUEST['activity_types'])) {
        $sql = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email.*,
                    iss_summary,
                    CONCAT(sup_ema_id, '-', sup_id) AS composite_id,
                    sta_color
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                WHERE
                    iss_sta_id = sta_id AND
                    sup_iss_id = iss_id AND
                    iss_prj_id = $prj_id AND\n";
        $sql .= createWhereClause('sup_date', 'sup_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            $data['email'] = processResult($res, 'sup_date', 'sup_iss_id');
        }
    }

    if (in_array('draft', $_REQUEST['activity_types'])) {
        $sql = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft.*,
                    iss_summary,
                    sta_color
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                WHERE
                    iss_sta_id = sta_id AND
                    emd_iss_id = iss_id AND
                    iss_prj_id = $prj_id AND\n";
        $sql .= createWhereClause('emd_updated_date', 'emd_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            $data['draft'] = processResult($res, 'emd_updated_date', 'emd_iss_id');
            foreach ($data['draft'] as &$draft) {
                if (!empty($draft['emd_unknown_user'])) {
                    $draft['from'] = $draft["emd_unknown_user"];
                } else {
                    $draft['from'] = User::getFromHeader($draft['emd_usr_id']);
                }
                list($draft['to'], ) = Draft::getEmailRecipients($draft['emd_id']);
                if (empty($draft['to'])) {
                    $draft['to'] = "Notification List";
                }
            }
        }
    }

    if (in_array('time', $_REQUEST['activity_types'])) {
        $sql = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking.*,
                    ttc_title,
                    iss_summary,
                    usr_full_name,
                    sta_color
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                WHERE
                    iss_sta_id = sta_id AND
                    ttr_iss_id = iss_id AND
                    ttr_ttc_id = ttc_id AND
                    ttr_usr_id = usr_id AND
                    iss_prj_id = $prj_id AND\n";
        $sql .= createWhereClause('ttr_created_date', 'ttr_usr_id');
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            $data['time'] = processResult($res, 'ttr_created_date', 'ttr_iss_id');
            foreach ($data['time'] as &$time) {
                $time['time_spent'] = Misc::getFormattedTime($time['ttr_time_spent'], true);
            }
        }
    }

    if ((empty($_REQUEST['developer'])) && (in_array('reminder', $_REQUEST['activity_types']))) {
        $sql = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_history.*,
                    iss_summary,
                    sta_color,
                    rma_title
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_history,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                WHERE
                    iss_sta_id = sta_id AND
                    rmh_iss_id = iss_id AND
                    rmh_rma_id = rma_id AND
                    iss_prj_id = $prj_id AND\n";
        $sql .= createWhereClause('rmh_created_date');
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            $data['reminder'] = processResult($res, 'rmh_created_date', 'rmh_iss_id');
        }
    }

    $tpl->assign(array(
        "unit"  =>  $_REQUEST['unit'],
        "amount"    =>  $_REQUEST['amount'],
        "developer" =>  $_REQUEST['developer'],
        "start_date"    =>  @$start_date,
        "end_date"      =>  @$end_date,
        "data"  =>  $data
    ));
}


function createWhereClause($date_field, $user_field = false)
{
    GLOBAL $start_date, $end_date;

    $sql = '';
    if ($_REQUEST['report_type'] == 'recent') {
        $sql .= "$date_field >= DATE_SUB('" . Date_Helper::getCurrentDateGMT() . "', INTERVAL " . Misc::escapeInteger($_REQUEST['amount']) . " " . Misc::escapeString($_REQUEST['unit']) . ")";
    } else {
        $sql .= "$date_field BETWEEN '$start_date' AND '$end_date'";
    }
    if (($user_field != false) && (!empty($_REQUEST['developer']))) {
        $sql .= " AND $user_field = " . Misc::escapeString($_REQUEST['developer']);
    }
    $sql .= " ORDER BY $date_field " . Misc::escapeString($_REQUEST['sort_order']);
    return $sql;
}

function processResult($results, $date_field, $issue_field)
{
    global $prj_id, $usr_id;

    $data = array();
    foreach ($results as &$res) {
        if (!Issue::canAccess($res[$issue_field], $usr_id)) {
            continue;
        }
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            try {
                $customer = $crm->getCustomer(Issue::getCustomerID($res[$issue_field]));
                $res['customer'] = $customer->getName();
            } catch (CRMException $e) {
                $res['customer'] = '';
            }
        }
        $res["date"] = Date_Helper::getFormattedDate($res[$date_field], Date_Helper::getPreferredTimezone($usr_id));
        // need to decode From:, To: mail headers
        if (isset($res["sup_from"])) {
            $res["sup_from"] = Mime_Helper::fixEncoding($res["sup_from"]);
        }
        if (isset($res["sup_to"])) {
            $res["sup_to"] = Mime_Helper::fixEncoding($res["sup_to"]);
        }

        $data[] = $res;
    }
    return $data;
}

$tpl->displayTemplate();
