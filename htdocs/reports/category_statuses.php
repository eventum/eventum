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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

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
