<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

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
