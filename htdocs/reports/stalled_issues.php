<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('reports/stalled_issues.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$prj_id = Auth::getCurrentProject();

if (count(@$_REQUEST['before']) < 1) {
    $before = date('Y-m-d', (time() - Date_Helper::MONTH));
} else {
    $before = implode('-', $_REQUEST['before']);
}
if (count(@$_REQUEST['after']) < 1) {
    $after = date('Y-m-d', (time() - Date_Helper::YEAR));
} else {
    $after = implode('-', $_REQUEST['after']);
}
if (empty($_REQUEST['sort_order'])) {
    $_REQUEST['sort_order'] = 'ASC';
}

$data = Report::getStalledIssuesByUser($prj_id, @$_REQUEST['developers'], @$_REQUEST['status'], $before, $after, $_REQUEST['sort_order']);

$groups = Group::getAssocList($prj_id);
$assign_options = array();
if ((count($groups) > 0) && (Auth::getCurrentRole() > User::ROLE_CUSTOMER)) {
    foreach ($groups as $grp_id => $grp_name) {
        $assign_options["grp:$grp_id"] = 'Group: ' . $grp_name;
    }
}
$assign_options += Project::getUserAssocList($prj_id, 'active', User::ROLE_USER);

$tpl->assign(array(
    'users' =>  $assign_options,
    'before_date'   =>  $before,
    'after_date'   =>  $after,
    'data'  =>  $data,
    'developers'    => @$_REQUEST['developers'],
    'status_list'   =>  Status::getAssocStatusList($prj_id),
    'status'        =>  @$_REQUEST['status'],
    'sort_order'    =>  $_REQUEST['sort_order'],
));

$tpl->displayTemplate();
