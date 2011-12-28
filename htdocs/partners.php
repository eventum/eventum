<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2011 Eventum Team              .                       |
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
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("select_partners.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$issue_id = @$_POST["issue_id"] ? $_POST["issue_id"] : $_GET["iss_id"];

if ((!Access::canViewDrafts($issue_id, Auth::getUserID())) || (Auth::getCurrentRole() <= User::getRoleID("Standard User"))) {
    $tpl = new Template_Helper();
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}
$prj_id = Issue::getProjectID($issue_id);

if (@$_POST["cat"] == "update") {
    $res = Partner::selectPartnersForIssue($_POST['issue_id'], @$_POST['partners']);
    $tpl->assign("update_result", $res);
}

$tpl->assign(array(
    "issue_id"           => $issue_id,
    'enabled_partners'   => Partner::getPartnersByProject($prj_id),
    'partners'           => Partner::getPartnersByIssue($issue_id),
    "current_user_prefs" => Prefs::get(Auth::getUserID())
));

$tpl->displayTemplate();
