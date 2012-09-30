<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2012 Eventum Team.                                     |
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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "scm");

$role_id = Auth::getCurrentRole();
if ($role_id == User::getRoleID('administrator')) {
    $tpl->assign("show_setup_links", true);
    if (@$_POST["cat"] == "update") {
        $setup = Setup::load();

        $setup["scm_integration"] = $_POST["scm_integration"];
        $setup["checkout_url"] = isset($_POST["checkout_url"]) ? $_POST["checkout_url"] : null;
        $setup["diff_url"] = isset($_POST["diff_url"]) ? $_POST["diff_url"] : null;
        $setup["scm_log_url"] = $_POST["scm_log_url"];

        $res = Setup::save($setup);
        $tpl->assign("result", $res);
    }
    $options = Setup::load(true);
    $tpl->assign("setup", $options);
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
