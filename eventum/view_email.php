<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                  |
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
// @(#) $Id: view_email.php 3258 2007-02-14 23:25:56Z glen $

require_once(dirname(__FILE__) . "/init.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.issue.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "class.support.php");
require_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("view_email.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);
$issue_id = Support::getIssueFromEmail($_GET["id"]);

if (!Issue::canAccess($issue_id, Auth::getUserID())) {
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

$email = Support::getEmailDetails($_GET["ema_id"], $_GET["id"]);
$email['seb_body'] = str_replace("&amp;nbsp;", "&nbsp;", $email['seb_body']);
$tpl->bulkAssign(array(
    "email"           => $email,
    "issue_id"        => $issue_id,
    'extra_title'     => "Email #" . $_GET['id'] . ": " . $email['sup_subject'],
    'email_accounts'  =>  Email_Account::getAssocList(array_keys(Project::getAssocList(Auth::getUserID())), true)
));

if (@$_GET['cat'] == 'list_emails') {
    $sides = Support::getListingSides($_GET["id"]);
    $tpl->assign(array(
        'previous' => $sides['previous'],
        'next'     => $sides['next']
    ));
} elseif ((@$_GET['cat'] == 'move_email') && (Auth::getCurrentRole() >= User::getRoleID("Standard User"))) {
    $res = Support::moveEmail(@$_GET['id'], @$_GET['ema_id'], @$_GET['new_ema_id']);
    $tpl->assign("move_email_result", $res);
    $tpl->assign("current_user_prefs", Prefs::get(Auth::getUserID()));
} else {
    $sides = Support::getIssueSides($issue_id, $_GET["id"]);
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
