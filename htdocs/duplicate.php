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
$tpl->setTemplate('duplicate.tpl.html');

Auth::checkAuthentication();

if (@$_POST['cat'] == 'mark') {
    Misc::mapMessages(Issue::markAsDuplicate($_POST['issue_id']), array(
            1   =>  array(ev_gettext('Thank you, the issue was marked as a duplicate successfully'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('Sorry, an error happened while trying to run your query.'), Misc::MSG_ERROR),
    ));

    Auth::redirect(APP_RELATIVE_URL . 'view.php?id=' . $_POST['issue_id']);
}

$tpl->displayTemplate();
