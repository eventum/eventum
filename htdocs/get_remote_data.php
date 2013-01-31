<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

Auth::checkAuthentication(APP_COOKIE);

/*
 * This page is used to return a single content to the expandable table using
 * httpClient library or jQuery.
 */

$valid_functions = array(
    'email' => 'getEmail',
    'note' => 'getNote',
    'draft' => 'getDraft',
    'phone' => 'getPhoneSupport',
    'mailqueue' => 'getMailQueue',
);
$action = Misc::escapeString($_REQUEST['action']);
if (in_array($action, array_keys($valid_functions))) {
    $method = $valid_functions[$action];
    $res = $method($_REQUEST['list_id']);
} else {
    $res = "ERROR: Unable to call function " . htmlspecialchars($action);
}

$callback = !empty($_GET['callback']) ? $_GET['callback'] : null;
// convert to wanted format
$res = array(
    'ec_id' => $_REQUEST['ec_id'],
    'list_id' => $_REQUEST['list_id'],
    'message' => $res,
);

if ($callback) {
    echo $callback, '(', json_encode($res), ')';
} else {
    /**
     * Since jsrs only supports returning one value, the string that is
     * returned is in the format of ec_id:id:<data>.
     *
     * If ec_id is not passed as a parameter, only the data is returned.
     */
    if (empty($_GET["ec_id"])) {
        echo $res['message'];
    } else {
        echo $res['ec_id'] . ':' . $res['list_id'] . ':' . $res['message'];
    }
}
exit;

/**
 * Selects the email from the table and returns the contents.
 *
 * @param   string $id The sup_ema_id and sup_id seperated by a -.
 * @return  A string containing the body of the email,
 */
function getEmail($id)
{
    $split = explode("-", $id);
    $info = Support::getEmailDetails($split[0], $split[1]);
    if (empty($_GET["ec_id"])) {
        return $info["seb_body"];
    }

    return Link_Filter::processText(Auth::getCurrentProject(), nl2br(Misc::highlightQuotedReply($info['seb_body'])));
}


/**
 * Selects a note from the table and returns the contents.
 *
 * @param   string $id The ID of this note.
 * @return  A string containing the note.
 */
function getNote($id)
{
    $note = Note::getDetails($id);
    if (empty($_GET["ec_id"])) {
        return $note["not_note"];
    }

    return Link_Filter::processText(Auth::getCurrentProject(), nl2br(Misc::highlightQuotedReply($note["not_note"])));
}


/**
 * Selects a draft from the table and returns the contents.
 *
 * @param   string $id The ID of this draft.
 * @return  A string containing the note.
 */
function getDraft($id)
{
    $info = Draft::getDetails($id);
    if (empty($_GET["ec_id"])) {
        return $info["emd_body"];
    }

    return Link_Filter::processText(Auth::getCurrentProject(), nl2br(htmlspecialchars($info["emd_body"])));
}


/**
 * Selects a phone support entry from the table and returns the contents.
 *
 * @param   string $id The phone support entry ID.
 * @return  A string containing the description.
 */
function getPhoneSupport($id)
{
    $res = Phone_Support::getDetails($id);
    if (empty($_GET["ec_id"])) {
        return $res["phs_description"];
    }

    return Link_Filter::processText(Auth::getCurrentProject(), nl2br(htmlspecialchars($res["phs_description"])));
}


/**
 * Selects a mail queue entry from the table and returns the contents.
 *
 * @param   string $id The mail queue entry ID.
 * @return  A string containing the body.
 */
function getMailQueue($id)
{
    if (Auth::getCurrentRole() < User::getRoleID('Developer')) {
        return;
    }

    $res = Mail_Queue::getEntry($id);
    if (empty($_GET["ec_id"])) {
        return $res["maq_body"];
    }

    return Link_Filter::processText(Auth::getCurrentProject(), nl2br(htmlspecialchars($res["maq_headers"] . "\n" . $res["maq_body"])));
}
