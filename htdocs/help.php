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
$tpl->setTemplate('help/index.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

if ((empty($_GET['topic'])) || (!Help::topicExists($_GET['topic']))) {
    $topic = 'main';
} else {
    $topic = $_GET['topic'];
}
$tpl->assign('topic', $topic);
$tpl->assign('links', Help::getNavigationLinks($topic));
if ($topic != 'main') {
    $tpl->assign('child_links', Help::getChildLinks($topic));
}

$tpl->displayTemplate();
