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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.download.php 1.14 04/01/26 20:37:04-06:00 joao@kickass. $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "db_access.php");

Auth::checkAuthentication(APP_COOKIE);

if (stristr(APP_BASE_URL, 'https:')) {
    // fix for IE 5.5/6 with SSL sites
    header("Pragma: cache");
}

if ($HTTP_GET_VARS['cat'] == 'attachment') {
    include_once(APP_INC_PATH . "class.attachment.php");
    $file = Attachment::getDetails($HTTP_GET_VARS["id"]);
    $php_extensions = array(
        "php",
        "php3",
        "php4",
        "phtml"
    );
    $filename = Attachment::nameToSafe($file["iaf_filename"]);
    $parts = pathinfo($filename);
    if (in_array(strtolower($parts["extension"]), $php_extensions)) {
        // instead of redirecting the user to a PHP script that may contain malicious code, we highlight the code
        highlight_string($file['iaf_file']);
    } else {
        $special_extensions = array(
            'err',
            'log',
            'cnf',
            'var',
            'ini',
            'java'
        );
        // always force the browser to display the contents of these special files
        if (in_array(strtolower($parts["extension"]), $special_extensions)) {
            header('Content-Type: text/plain');
        } else {
            if (empty($file['iaf_filetype'])) {
                header("Content-Type: application/unknown");
            } else {
                header("Content-Type: " . $file['iaf_filetype']);
            }
            header("Content-Disposition: attachment; filename=$filename");
        }
        header("Content-Length: " . $file['iaf_filesize']);
        echo $file['iaf_file'];
        exit;
    }
}
?>
