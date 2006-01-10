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
// @(#) $Id: s.post.php 1.8 03/07/11 05:04:05-00:00 jpm $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("post.tpl.html");

if (@$HTTP_POST_VARS["cat"] == "report") {
    $res = Issue::addAnonymousReport();
    if ($res != -1) {
        // show direct links to the issue page, issue listing page and email listing page
        $tpl->assign("new_issue_id", $res);
    } else {
        // need to show everything again
        $tpl->assign("error_msg", "1");
    }
} elseif (@$HTTP_GET_VARS["post_form"] == "yes") {
    // only list those projects that are allowing anonymous reporting of new issues
    $projects = Project::getAnonymousList();
    if (empty($projects)) {
        $tpl->assign("no_projects", "1");
    } else {
        if (!in_array($HTTP_GET_VARS["project"], array_keys($projects))) {
            $tpl->assign("no_projects", "1");
        } else {
            // get list of custom fields for the selected project
            $options = Project::getAnonymousPostOptions($HTTP_GET_VARS["project"]);
            if (@$options["show_custom_fields"] == "yes") {
                $tpl->assign("custom_fields", Custom_Field::getListByProject($HTTP_GET_VARS["project"], 'anonymous_form'));
            }
            $tpl->assign("project_name", Project::getName($HTTP_GET_VARS["project"]));
        }
    }
} else {
    // only list those projects that are allowing anonymous reporting of new issues
    $projects = Project::getAnonymousList();
    if (empty($projects)) {
        $tpl->assign("no_projects", "1");
    } else {
        if (count($projects) == 1) {
            $project_ids = array_keys($projects);
            Auth::redirect('post.php?post_form=yes&project=' . $project_ids[0]);
        } else {
            $tpl->assign("projects", $projects);
        }
    }
}

$tpl->displayTemplate();
?>