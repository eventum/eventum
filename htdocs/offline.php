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

// this file may be called from db_helper, so init already called
if (!defined('APP_PATH')) {
    require_once __DIR__ . '/../init.php';
}

$tpl = new Template_Helper();
if (PHP_SAPI === 'cli') {
    $tpl->setTemplate('offline.tpl.text');
} else {
    $tpl->setTemplate('offline.tpl.html');
}

$tpl->assign('error_type', $error_type);

$tpl->displayTemplate();
