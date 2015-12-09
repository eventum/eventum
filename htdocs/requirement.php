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
$tpl->setTemplate('requirement.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

if (@$_POST['cat'] == 'set_analysis') {
    $res = Impact_Analysis::update($_POST['isr_id']);
    $tpl->assign('set_analysis_result', $res);
}

$tpl->displayTemplate();
