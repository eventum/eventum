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
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.attachment.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("file_upload.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);
$usr_id = Auth::getUserID();
$issue_id = @$HTTP_POST_VARS["issue_id"] ? $HTTP_POST_VARS["issue_id"] : $HTTP_GET_VARS["iss_id"];

if (@$HTTP_POST_VARS["cat"] == "upload_file") {
    $res = Attachment::attach($usr_id, $HTTP_POST_VARS['status']);
    $tpl->assign("upload_file_result", $res);
}

$tpl->assign(array(
    "issue_id"           => $issue_id,
    "current_user_prefs" => Prefs::get(Auth::getUserID()),
    "max_attachment_size"    => Attachment::getMaxAttachmentSize()
));

$tpl->displayTemplate();
?>