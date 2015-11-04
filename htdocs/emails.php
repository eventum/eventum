<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('emails.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessAssociateEmails(Auth::getUserID())) {
    $tpl->assign('no_access', 1);
    $tpl->displayTemplate();
    exit;
}

// accept prj_id from GET to ease administration (link bookmarking)
$prj_id = isset($_GET['prj_id']) ? (int) $_GET['prj_id'] : null;
if ($prj_id && $prj_id != Auth::getCurrentProject()) {
    AuthCookie::setProjectCookie($prj_id);
}

$pagerRow = Support::getParam('pagerRow');
if (empty($pagerRow)) {
    $pagerRow = 0;
}
$rows = Support::getParam('rows');
if (empty($rows)) {
    $rows = APP_DEFAULT_PAGER_SIZE;
}

$options = Support::saveSearchParams();
$tpl->assign('options', $options);
$tpl->assign('sorting', Support::getSortingInfo($options));

$list = Support::getEmailListing($options, $pagerRow, $rows);
$tpl->assign('list', $list['list']);
$tpl->assign('list_info', $list['info']);
$tpl->assign('issues', Issue::getColList());
$tpl->assign('accounts', Email_Account::getAssocList($prj_id));

$prefs = Prefs::get(Auth::getUserID());
$tpl->assign('refresh_rate', $prefs['email_refresh_rate'] * 60);
$tpl->assign('refresh_page', 'emails.php');

$tpl->displayTemplate();
