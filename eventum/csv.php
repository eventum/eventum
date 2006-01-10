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
// @(#) $Id: s.csv.php 1.4 03/01/16 01:47:31-00:00 jpm $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "db_access.php");

Auth::checkAuthentication(APP_COOKIE);

$csv = base64_decode($HTTP_POST_VARS["csv_data"]);

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Pragma: no-cache");
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=".uniqid('').'.xls');
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: \"inline\"");
header("Content-Length: " . strlen($csv));
echo $csv;
?>