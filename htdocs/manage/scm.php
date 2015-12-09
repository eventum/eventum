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
$tpl->setTemplate('manage/scm.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_REPORTER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'update') {
    $res = Setup::save(array('scm_integration' => $_POST['scm_integration']));
    $tpl->assign('result', $res);

    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the setup information was saved successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext(
                "ERROR: The system doesn't have the appropriate permissions to create the configuration file in the setup directory (%1\$s). ".
                'Please contact your local system administrator and ask for write privileges on the provided path.', APP_CONFIG_PATH),
                Misc::MSG_NOTE_BOX),
            -2  =>  array(ev_gettext(
                "ERROR: The system doesn't have the appropriate permissions to update the configuration file in the setup directory (%1\$s). ".
                'Please contact your local system administrator and ask for write privileges on the provided filename.', APP_SETUP_FILE),
                Misc::MSG_NOTE_BOX),
    ));
}

$tpl->assign('setup', Setup::get());

$tpl->displayTemplate();
