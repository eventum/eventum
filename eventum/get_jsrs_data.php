<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// +----------------------------------------------------------------------+
//
// @(#) $Id: $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.draft.php");
include_once(APP_INC_PATH . "jsrsServer.inc.php");

/*
 * This page is used to return a single content to the expandable table using jsrs.
 */

jsrsDispatch("getEmail,getNote,getDraft");

/**
 * Selects the email from the table and returns the contents. Since jsrs only supports returning one value, 
 * the string that is returned is in the format
 * of ec_id:id:email. If ec_id is not passed as a parameter, only the email is returned.
 * 
 * @param   string $id The sup_ema_id and sup_id seperated by a -.
 * @return  A string containing the body of the email, optionally prefaced by the ec_id and $id.
 */
function getEmail($id)
{
    $split = explode("-", $id);
    $info = Support::getEmailDetails($split[0],$split[1]);
    if (!empty($_GET["ec_id"])) {
        return nl2br(htmlspecialchars($_GET["ec_id"] . ":" . $id. ":" . $info["seb_body"]));
    } else {
        return $info["seb_body"];
    }
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
    if (!empty($_GET["ec_id"])) {
        return nl2br(htmlspecialchars($_GET["ec_id"] . ":" . $id. ":" . $note["not_note"]));
    } else {
        return $note["not_note"];
    }
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
    if (!empty($_GET["ec_id"])) {
        return nl2br(htmlspecialchars($_GET["ec_id"] . ":" . $id. ":" . $info["emd_body"]));
    } else {
        return $info["emd_body"];
    }
}
?>