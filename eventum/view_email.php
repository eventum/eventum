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
// @(#) $Id: s.view_email.php 1.8 03/11/12 19:57:26-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("view_email.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$email = Support::getEmailDetails($HTTP_GET_VARS["ema_id"], $HTTP_GET_VARS["id"]);
$email["message"] = str_replace("&amp;nbsp;", "&nbsp;",$email["message"]);

$issue_id = Support::getIssueFromEmail($HTTP_GET_VARS["id"]);

if (!Issue::canAccess($issue_id, Auth::getUserID())) {
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

$tpl->bulkAssign(array(
    "email"           => $email,
    "issue_id"        => $issue_id,
    'extra_title'     => "Email #" . $HTTP_GET_VARS['id'] . ": " . $email['sup_subject'],
    'email_accounts'  =>  Email_Account::getAssocList(array_keys(Project::getAssocList(Auth::getUserID())), true)
));

if (@$HTTP_GET_VARS['cat'] == 'list_emails') {
    $sides = Support::getListingSides($HTTP_GET_VARS["id"]);
    $tpl->assign(array(
        'previous' => $sides['previous'],
        'next'     => $sides['next']
    ));
} elseif ((@$HTTP_GET_VARS['cat'] == 'move_email') && (Auth::getCurrentRole() >= User::getRoleID("Standard User"))) {
    $res = Support::moveEmail(@$HTTP_GET_VARS['id'], @$HTTP_GET_VARS['ema_id'], @$HTTP_GET_VARS['new_ema_id']);
    $tpl->assign("move_email_result", $res);
    $tpl->assign("current_user_prefs", Prefs::get(Auth::getUserID()));
} else {
    $sides = Support::getIssueSides($issue_id, $HTTP_GET_VARS["id"]);
    $tpl->assign(array(
        'previous' => $sides['previous'],
        'next'     => $sides['next']
    ));
}

// set the page charset to whatever is set on this email
$charset = Mime_Helper::getCharacterSet($email['seb_full_email']);
if (!empty($charset)) {
    header("Content-Type: text/html; charset=" . $charset);
}

$tpl->displayTemplate();
?>