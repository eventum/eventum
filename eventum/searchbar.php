<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
// @(#) $Id: s.searchbar.php 1.3 03/09/26 02:06:54-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.category.php");
include_once(APP_INC_PATH . "class.priority.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.release.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.filter.php");
include_once(APP_INC_PATH . "class.status.php");

$tpl = new Template_API();
$tpl->setTemplate("searchbar.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$prj_id = Auth::getCurrentProject();
$tpl->assign("priorities", Priority::getList($prj_id));
$tpl->assign("status", Status::getAssocStatusList($prj_id));
$tpl->assign("users", Project::getUserAssocList($prj_id));
$tpl->assign("categories", Category::getAssocList($prj_id));

$tpl->assign("custom", Filter::getListing($prj_id));

$options = Issue::saveSearchParams();
$tpl->assign("options", $options);

$tpl->displayTemplate();
?>