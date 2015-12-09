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
$tpl->setTemplate('spell_check.tpl.html');

Auth::checkAuthentication();

if (!empty($_GET['form_name'])) {
    // show temporary form
    $tpl->assign('show_temp_form', 'yes');
} else {
    $tpl->assign('spell_check', Misc::checkSpelling($_POST['textarea']));
}

$tpl->displayTemplate();
