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
$tpl->setTemplate('mail_queue.tpl.html');

Auth::checkAuthentication();

$issue_id = $_GET['iss_id'];

if ((Auth::getCurrentRole() < User::ROLE_DEVELOPER) ||
        (Issue::getProjectID($issue_id) != Auth::getCurrentProject())) {
    $tpl->assign('denied', 1);
} else {
    $data = Mail_Queue::getListByIssueID($issue_id);

    $tpl->assign(array(
                    'data'  =>  $data,
                    'issue_id'  =>  $issue_id,
    ));
}
$tpl->displayTemplate();
