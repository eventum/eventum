<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("view_note.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);
$usr_id = Auth::getUserID();

$note_id = $_GET["id"];
$note = Note::getDetails($note_id);

if ($note == '') {
    $tpl->assign("note", '');
    $tpl->displayTemplate();
    exit;
} else {
    $note["message"] = $note["not_note"];
    $issue_id = Note::getIssueID($note_id);
    $usr_id = Auth::getUserID();
}

if ((User::getRoleByUser($usr_id, Issue::getProjectID($issue_id)) < User::getRoleID('Standard User')) || (!Access::canViewInternalNotes($issue_id, Auth::getUserID()))) {
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

$note = Note::getDetails($_GET["id"]);
$note["message"] = $note["not_note"];

$issue_id = Note::getIssueID($_GET["id"]);
$tpl->bulkAssign(array(
    "note"        => $note,
    "issue_id"    => $issue_id,
    'extra_title' => "Note #" . Note::getNoteSequenceNumber($issue_id, $note_id) . ": " . $note['not_title'],
    'recipients'  => Mail_Queue::getMessageRecipients('notes', $note_id),
));

if (!empty($issue_id)) {
    $sides = Note::getSideLinks($issue_id, $_GET["id"]);
    $tpl->assign(array(
        'previous' => $sides['previous'],
        'next'     => $sides['next']
    ));
}

$tpl->displayTemplate();
