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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/email_accounts.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("all_projects", Project::getAll());

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('administrator')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

if (@$_POST["cat"] == "new") {
    Misc::mapMessages(Email_Account::insert(), array(
            1   =>  array('Thank you, the email account was added successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to add the new account.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    Misc::mapMessages(Email_Account::update(), array(
            1   =>  array('Thank you, the email account was updated successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to update the account information.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    Misc::mapMessages(Email_Account::remove(), array(
            1   =>  array('Thank you, the email account was deleted successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to delete the account information.', Misc::MSG_ERROR),
    ));
}

if (@$_GET["cat"] == "edit") {
    $tpl->assign("info", Email_Account::getDetails($_GET["id"]));
}
$tpl->assign("list", Email_Account::getList());

$tpl->displayTemplate();
