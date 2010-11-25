<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2010 Sun Microsystem Inc.                              |
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

$tpl->assign("type", "monitor");

$role_id = Auth::getCurrentRole();
if ($role_id == User::getRoleID('administrator')) {
    $tpl->assign("show_setup_links", true);

    $tpl->assign("project_list", Project::getAll());

    if (!empty($_POST['cat']) && $_POST["cat"] == 'update') {
        $setup = Setup::load();
        $setup['monitor']['diskcheck'] = $_POST["diskcheck"];
        $setup['monitor']['paths'] = $_POST["paths"];
        $setup['monitor']['ircbot'] = $_POST["ircbot"];
        $res = Setup::save($setup);
        $tpl->assign("result", $res);
    }

    $tpl->assign("enable_disable", array(
        "enabled" => ev_gettext("Enabled"),
        "disabled" => ev_gettext("Disabled"),
    ));

    $options = Setup::load(true);
    $tpl->assign("setup", $options);
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
