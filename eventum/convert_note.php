<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
//
// @(#) $Id$
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.draft.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.notification.php");

$tpl = new Template_API();
$tpl->setTemplate("convert_note.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

if (@$HTTP_POST_VARS['cat'] == 'convert') {
    if (@$HTTP_POST_VARS["add_authorized_replier"] == 1) {
        $authorize_sender = true;
    } else {
        $authorize_sender = false;
    }
    $tpl->assign("convert_result", Note::convertNote($HTTP_POST_VARS['note_id'], $HTTP_POST_VARS['target'], $authorize_sender));
} else {
    $tpl->assign("note_id", $HTTP_GET_VARS['id']);
}

$tpl->assign("current_user_prefs", Prefs::get(Auth::getUserID()));

$tpl->displayTemplate();
?>