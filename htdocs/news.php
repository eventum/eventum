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
$tpl->setTemplate('news.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
if (!empty($_GET['id'])) {
    $t = News::getDetails($_GET['id']);
    $tpl->assign('news', array($t));
} else {
    $tpl->assign('news', News::getListByProject($prj_id, true));
}

$tpl->displayTemplate();
