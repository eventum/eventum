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
$tpl->setTemplate('custom_fields_form.tpl.html');

Auth::checkAuthentication();

$prj_id = Auth::getCurrentProject();
$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : $_GET['issue_id'];

if (!Issue::canUpdate($issue_id, Auth::getUserID())) {
    $tpl = new Template_Helper();
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'update_values') {
    $res = Custom_Field::updateFromPost(true);
    if (is_array($res)) {
        $res = 1;
    }
    $tpl->assign('update_result', $res);
}

$prefs = Prefs::get(Auth::getUserID());
$tpl->assign('current_user_prefs', $prefs); // XXX: use 'user_prefs' recursively
$tpl->assign('user_prefs', $prefs);

$tpl->assign('issue_id', $issue_id);
$tpl->assign('custom_fields', Custom_Field::getListByIssue($prj_id, $issue_id));

$tpl->displayTemplate();
