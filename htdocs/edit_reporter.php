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
// | Authors: Dave Anderson <dave@anderson.net.nz>                        |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("edit_reporter.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$issue_id = @$_POST["issue_id"] ? $_POST["issue_id"] : $_GET["iss_id"];
$tpl->assign("issue_id", $issue_id);

if (!Access::canChangeReporter($issue_id, Auth::getUserID())) {
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

if (@$_POST["cat"] == "update") {
    $res = Edit_Reporter::update($issue_id, $_POST['email']);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the Reporter was updated successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to update the Reporter.', Misc::MSG_ERROR),
    ));
    Auth::redirect(APP_RELATIVE_URL . 'view.php?id=' . $issue_id);
}

$t = Project::getAddressBook($prj_id, $issue_id);
$tpl->assign("allowed_reporters", $t);

$tpl->displayTemplate();
