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

Auth::checkAuthentication();

$prj_id = $_POST['current_project'];
$url = $_SERVER['HTTP_REFERER'];

AuthCookie::setProjectCookie($prj_id);
Misc::setMessage(ev_gettext('The project has been switched'), Misc::MSG_INFO);

// if url is 'view.php', use 'list.php',
// otherwise autoswitcher will switch back to the project where the issue was :)
if (!$url || stristr($url, 'view.php') !== false) {
    $url = APP_RELATIVE_URL . 'list.php';
}

Auth::redirect($url);
