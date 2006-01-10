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
// @(#) $Id: s.get_attachment.php 1.5 03/09/30 18:07:03-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "db_access.php");

Auth::checkAuthentication(APP_COOKIE);

if (@$HTTP_GET_VARS['cat'] == 'blocked_email') {
    $email = Note::getBlockedMessage($HTTP_GET_VARS["note_id"]);
} else {
    $email = Support::getFullEmail($HTTP_GET_VARS["sup_id"]);
}
if (!empty($HTTP_GET_VARS['cid'])) {
    list($mimetype, $data) = Mime_Helper::getAttachment($email, $HTTP_GET_VARS["filename"], $HTTP_GET_VARS["cid"]);
} else {
    list($mimetype, $data) = Mime_Helper::getAttachment($email, $HTTP_GET_VARS["filename"]);
}
Attachment::outputDownload($data, $HTTP_GET_VARS["filename"], strlen($data), $mimetype);
?>