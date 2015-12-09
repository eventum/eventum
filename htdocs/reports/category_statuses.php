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
$tpl->setTemplate('reports/category_statuses.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

// TODO: move this query to some class

$prj_id = Auth::getCurrentProject();
$categories = Category::getAssocList($prj_id);
$statuses = Status::getAssocStatusList($prj_id, true);

$data = array();
foreach ($categories as $cat_id => $cat_title) {
    $data[$cat_id] = array(
        'title' =>  $cat_title,
        'statuses'  =>  array(),
    );
    foreach ($statuses as $sta_id => $sta_title) {
        $sql = 'SELECT
                    count(*)
                FROM
                    {{%issue}}
                WHERE
                    iss_prj_id = ? AND
                    iss_sta_id = ? AND
                    iss_prc_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($prj_id, $sta_id, $cat_id));
        } catch (DbException $e) {
            break 2;
        }
        $data[$cat_id]['statuses'][$sta_id] = array(
            'title' =>  $sta_title,
            'count' =>  $res,
        );
    }
}

$tpl->assign(array(
    'statuses'  =>  $statuses,
    'categories'    =>  $categories,
    'data'  =>  $data,
));

$tpl->displayTemplate();
