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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//
require_once("../config.inc.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.report.php");
require_once(APP_INC_PATH . "class.date.php");
require_once(APP_INC_PATH . "db_access.php");
require_once(APP_INC_PATH . "class.customer.php");
require_once(APP_INC_PATH . "class.customer_stats_report.php");
require_once(APP_INC_PATH . "class.session.php");

$tpl = new Template_API();
$tpl->setTemplate("reports/estimated_dev_time.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

$sql = "SELECT
            prc_id,
        	prc_title,
        	SUM(iss_dev_time) as dev_time
        FROM
        	" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
        	" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category,
        	" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
        WHERE
        	iss_prc_id = prc_id AND
        	iss_sta_id = sta_id AND
        	sta_is_closed != 1 AND
        	iss_prj_id = " . Auth::getCurrentProject() . "
        GROUP BY
        	iss_prc_id";
$res = $GLOBALS["db_api"]->dbh->getAll($sql, DB_FETCHMODE_ASSOC);
if (PEAR::isError($res)) {
    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
    return false;
}
$total = 0;
foreach ($res as $id => $row) {
    $total += $row['dev_time'];
    $res[$id]['dev_time'] = str_replace(" ", "&nbsp;", str_pad($row['dev_time'], 5, ' ', STR_PAD_LEFT));
}
$res[] = array(
    'dev_time'  =>  str_replace(" ", "&nbsp;", str_pad($total, 5, ' ', STR_PAD_LEFT)),
    'prc_title' =>  'Total'
);
$tpl->assign("data", $res);

$tpl->displayTemplate();
?>