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

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('adv_search.tpl.html');

Auth::checkAuthentication();

// customers should not be able to see this page
$role_id = Auth::getCurrentRole();
if ($role_id == User::ROLE_CUSTOMER) {
    Auth::redirect('list.php');
}

$prj_id = Auth::getCurrentProject();

// generate options for assign list. If there are groups and user is above a customer, include groups
$groups = Group::getAssocList($prj_id);
$users = Project::getUserAssocList($prj_id, 'active', User::ROLE_CUSTOMER);
$assign_options = array(
    ''      =>  ev_gettext('Any'),
    '-1'    =>  ev_gettext('un-assigned'),
    '-2'    =>  ev_gettext('myself and un-assigned'),
);

if (Auth::isAnonUser()) {
    unset($assign_options['-2']);
} elseif (User::getGroupID(Auth::getUserID()) != '') {
    $assign_options['-3'] = ev_gettext('myself and my group');
    $assign_options['-4'] = ev_gettext('myself, un-assigned and my group');
}
if ((count($groups) > 0) && ($role_id > User::ROLE_CUSTOMER)) {
    foreach ($groups as $grp_id => $grp_name) {
        $assign_options["grp:$grp_id"] = 'Group: ' . $grp_name;
    }
}
$assign_options += $users;

$tpl->assign(array(
    'cats'          => Category::getAssocList($prj_id),
    'priorities'    => Priority::getList($prj_id),
    'severities'    => Severity::getList($prj_id),
    'status'        => Status::getAssocStatusList($prj_id),
    'users'         => $assign_options,
    'releases'      => Release::getAssocList($prj_id, true),
    'custom'        => Filter::getListing($prj_id),
    'custom_fields' =>  Custom_Field::getListByProject($prj_id, ''),
    'reporters'     => Project::getReporters($prj_id),
    'products'      => Product::getAssocList(false),
));

if (!empty($_GET['custom_id'])) {
    $check_perm = true;
    if (Filter::isGlobal($_GET['custom_id'])) {
        if ($role_id >= User::ROLE_MANAGER) {
            $check_perm = false;
        }
    }
    $options = Filter::getDetails($_GET['custom_id'], $check_perm);
} else {
    $options = array();
    $options['cst_rows'] = APP_DEFAULT_PAGER_SIZE;
}
$tpl->assign('options', $options);

$tpl->displayTemplate();
