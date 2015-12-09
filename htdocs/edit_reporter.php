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
$tpl->setTemplate('edit_reporter.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : $_GET['iss_id'];
$tpl->assign('issue_id', $issue_id);

if (!Access::canChangeReporter($issue_id, Auth::getUserID())) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'update') {
    $res = Edit_Reporter::update($issue_id, trim($_POST['email']));
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the Reporter was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the Reporter.'), Misc::MSG_ERROR),
    ));
    Auth::redirect(APP_RELATIVE_URL . 'view.php?id=' . $issue_id);
}

$t = Project::getAddressBook($prj_id, $issue_id);
$tpl->assign('allowed_reporters', $t);

$tpl->displayTemplate();
