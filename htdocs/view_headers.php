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
$tpl->setTemplate('view_headers.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$cat = isset($_GET['cat']) ? (string) $_GET['cat'] : null;
if ($cat == 'note') {
    $headers = Note::getBlockedMessage($_GET['id']);
} else {
    $headers = Support::getFullEmail($_GET['id']);
}
$tpl->assign('headers', $headers);

$tpl->displayTemplate();
