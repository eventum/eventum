<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("authorized_replier.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$issue_id = @$_POST["issue_id"] ? $_POST["issue_id"] : $_GET["iss_id"];
$tpl->assign("issue_id", $issue_id);

if (@$_POST["cat"] == "insert") {
    $res = Authorized_Replier::manualInsert($issue_id, $_POST['email']);
    if ($res == 1) {
        Misc::setMessage(ev_gettext("Thank you, the authorized replier was inserted successfully."));
    } elseif ($res == -1) {
        Misc::setMessage(ev_gettext("An error occurred while trying to insert the authorized replier."), Misc::MSG_ERROR);
    } elseif ($res == -2) {
        Misc::setMessage(ev_gettext("Users with a role of 'customer' or below are not allowed to be added to the authorized repliers list."), Misc::MSG_ERROR);
    }
} elseif (@$_POST["cat"] == "delete") {
    $res = Authorized_Replier::removeRepliers($_POST["items"]);
    if ($res == 1) {
        Misc::setMessage(ev_gettext("Thank you, the authorized replier was deleted successfully."));
    } elseif ($res == -1) {
        Misc::setMessage(ev_gettext("An error occurred while trying to delete the authorized replier."), Misc::MSG_ERROR);
    }
}

list(,$repliers) = Authorized_Replier::getAuthorizedRepliers($issue_id);
$tpl->assign("list", $repliers);

$t = Project::getAddressBook($prj_id, $issue_id);
$tpl->assign("assoc_users", $t);

$tpl->displayTemplate();
