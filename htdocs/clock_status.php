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

Auth::checkAuthentication('index.php?err=5', true);

$usr_id = Auth::getUserID();

if (User::isClockedIn($usr_id)) {
    User::ClockOut($usr_id);
    Misc::setMessage(ev_gettext('You have been clocked out'), Misc::MSG_INFO);
} else {
    User::ClockIn($usr_id);
    Misc::setMessage(ev_gettext('You have been clocked in'), Misc::MSG_INFO);
}

Auth::redirect(!empty($_REQUEST['current_page']) ? $_REQUEST['current_page'] : APP_RELATIVE_URL . 'list.php');
