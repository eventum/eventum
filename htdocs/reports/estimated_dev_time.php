<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('reports/estimated_dev_time.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

// TODO: move this query to some class
$sql = 'SELECT
            prc_id,
        	prc_title,
        	SUM(iss_dev_time) as dev_time
        FROM
        	{{%issue}},
        	{{%project_category}},
        	{{%status}}
        WHERE
        	iss_prc_id = prc_id AND
        	iss_sta_id = sta_id AND
        	sta_is_closed != 1 AND
        	iss_prj_id = ?
        GROUP BY
        	iss_prc_id';
try {
    $res = DB_Helper::getInstance()->getAll($sql, array(Auth::getCurrentProject()));
} catch (DbException $e) {
    return false;
}
$total = 0;
foreach ($res as $id => $row) {
    $total += $row['dev_time'];
    $res[$id]['dev_time'] = str_replace(' ', '&nbsp;', str_pad($row['dev_time'], 5, ' ', STR_PAD_LEFT));
}
$res[] = array(
    'dev_time'  =>  str_replace(' ', '&nbsp;', str_pad($total, 5, ' ', STR_PAD_LEFT)),
    'prc_title' =>  'Total',
);
$tpl->assign('data', $res);

$tpl->displayTemplate();
