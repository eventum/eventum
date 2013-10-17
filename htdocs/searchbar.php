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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

Auth::checkAuthentication(APP_COOKIE);

if (!empty($_GET['custom_id'])) {
    $filters = Filter::getListing(true);
    foreach ($filters as $filter) {
        if ($filter['cst_id'] == (int )$_GET['custom_id']) {
            parse_str($filter['url'], $params);
            $params = array_merge($params, $_POST, $_GET);
            unset($params['custom_id']);
            $url = 'list.php?cat=search&' . http_build_query($params);
            Auth::redirect($url);
        }
    }
}

$tpl = new Template_Helper();
$tpl->setTemplate("searchbar.tpl.html");

$prj_id = Auth::getCurrentProject();
$tpl->assign("priorities", Priority::getList($prj_id));
$tpl->assign("status", Status::getAssocStatusList($prj_id));
$tpl->assign("users", Project::getUserAssocList($prj_id));
$tpl->assign("categories", Category::getAssocList($prj_id));

$tpl->assign("custom", Filter::getListing($prj_id));

$options = Search::saveSearchParams();
$tpl->assign("options", $options);

$tpl->displayTemplate();
