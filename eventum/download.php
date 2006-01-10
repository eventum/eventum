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
// @(#) $Id: s.download.php 1.14 04/01/26 20:37:04-06:00 joao@kickass. $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.attachment.php");
include_once(APP_INC_PATH . "db_access.php");

Auth::checkAuthentication(APP_COOKIE);


if (stristr(APP_BASE_URL, 'https:')) {
    // fix for IE 5.5/6 with SSL sites
    header('Pragma: cache');
}
// fix for IE6 (KB812935)
header('Cache-Control: must-revalidate');

if ($HTTP_GET_VARS['cat'] == 'attachment') {
    $file = Attachment::getDetails($HTTP_GET_VARS["id"]);
    if (!empty($file)) {
        if (!Issue::canAccess($file['iat_iss_id'], Auth::getUserID())) {
            $tpl = new Template_API();
            $tpl->setTemplate("permission_denied.tpl.html");
            $tpl->displayTemplate();
            exit;
        }
        Attachment::outputDownload($file['iaf_file'], $file["iaf_filename"], $file['iaf_filesize'], $file['iaf_filetype']);
    }
}
?>
