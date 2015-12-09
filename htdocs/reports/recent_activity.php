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

// This report shows a list of activity performed in recent history.
$tpl = new Template_Helper();
$tpl->setTemplate('reports/recent_activity.tpl.html');

Auth::checkAuthentication();

try {
    $controller = new RecentActivity();
    $controller($tpl);
} catch (LogicException $e) {
    echo $e->getMessage();
    exit;
}

$tpl->displayTemplate();
