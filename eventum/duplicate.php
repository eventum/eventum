<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                        |
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
// @(#) $Id: s.duplicate.php 1.2 03/05/23 05:01:25-00:00 jpm $
//
require_once("config.inc.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.issue.php");
require_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("duplicate.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (@$_POST["cat"] == "mark") {
    $res = Issue::markAsDuplicate($_POST["issue_id"]);
    $tpl->assign("duplicate_result", $res);
} else {
    // need to show only the issues that have iss_duplicated_iss_id = NULL
    $tpl->assign("issues", Issue::getColList("iss_duplicated_iss_id IS NULL AND iss_id <> " . $_GET["id"]));
    $tpl->assign("assoc_issues", Issue::getAssocList("iss_duplicated_iss_id IS NULL AND iss_id <> " . $_GET["id"]));
}

$tpl->displayTemplate();
?>