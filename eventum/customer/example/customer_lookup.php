<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007, 2008 MySQL AB                        |
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
// @(#) $Id: customer_lookup.php 3555 2008-03-15 16:45:34Z glen $
//
require_once(dirname(__FILE__) . "/../../init.php");
require_once(APP_INC_PATH . "db_access.php");
require_once(APP_INC_PATH . "class.template.php");

$tpl = new Template_API();
$tpl->setTemplate("customer/example/customer_lookup.tpl.html");

Auth::checkAuthentication(APP_COOKIE);
$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

// only customers should be able to use this page
$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('Developer')) {
    Auth::redirect(APP_RELATIVE_URL . "list.php");
}

if (@$_POST['cat'] == 'lookup') {
    $tpl->assign("results", Customer::lookup($prj_id, $_POST['field'], $_POST['value']));
}

$tpl->displayTemplate();
