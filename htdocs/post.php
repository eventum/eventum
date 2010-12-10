<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("post.tpl.html");

if (@$_POST["cat"] == "report") {
    $res = Issue::addAnonymousReport();
    if ($res != -1) {
        // show direct links to the issue page, issue listing page and email listing page
        $tpl->assign("new_issue_id", $res);
    } else {
        // need to show everything again
        $tpl->assign("error_msg", "1");
    }
} elseif (@$_GET["post_form"] == "yes") {
    // only list those projects that are allowing anonymous reporting of new issues
    $projects = Project::getAnonymousList();
    if (empty($projects)) {
        $tpl->assign("no_projects", "1");
    } else {
        if (!in_array($_GET["project"], array_keys($projects))) {
            $tpl->assign("no_projects", "1");
        } else {
            // get list of custom fields for the selected project
            $options = Project::getAnonymousPostOptions($_GET["project"]);
            if (@$options["show_custom_fields"] == "yes") {
                $tpl->assign("custom_fields", Custom_Field::getListByProject($_GET["project"], 'anonymous_form'));
            }
            $tpl->assign("project_name", Project::getName($_GET["project"]));
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
