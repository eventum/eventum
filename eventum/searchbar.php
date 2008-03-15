<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007, 2008 MySQL AB            |
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
// @(#) $Id: searchbar.php 3555 2008-03-15 16:45:34Z glen $

require_once(dirname(__FILE__) . "/init.php");
require_once(APP_INC_PATH . "db_access.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.category.php");
require_once(APP_INC_PATH . "class.priority.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "class.release.php");
require_once(APP_INC_PATH . "class.project.php");
require_once(APP_INC_PATH . "class.filter.php");
require_once(APP_INC_PATH . "class.status.php");

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
