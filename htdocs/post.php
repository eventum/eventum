<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('post.tpl.html');

if (@$_POST['cat'] == 'report') {
    $res = Issue::addAnonymousReport();
    if ($res != -1) {
        // show direct links to the issue page, issue listing page and email listing page
        $tpl->assign('new_issue_id', $res);
    } else {
        // need to show everything again
        $tpl->assign('error_msg', '1');
    }
} elseif (@$_GET['post_form'] == 'yes') {
    // only list those projects that are allowing anonymous reporting of new issues
    $projects = Project::getAnonymousList();
    if (empty($projects)) {
        $tpl->assign('no_projects', '1');
    } else {
        if (!in_array($_GET['project'], array_keys($projects))) {
            $tpl->assign('no_projects', '1');
        } else {
            // get list of custom fields for the selected project
            $options = Project::getAnonymousPostOptions($_GET['project']);
            if (@$options['show_custom_fields'] == 'yes') {
                $tpl->assign('custom_fields', Custom_Field::getListByProject($_GET['project'], 'anonymous_form'));
            }
            $tpl->assign('project_name', Project::getName($_GET['project']));
        }
    }
} else {
    // only list those projects that are allowing anonymous reporting of new issues
    $projects = Project::getAnonymousList();
    if (empty($projects)) {
        $tpl->assign('no_projects', '1');
    } else {
        if (count($projects) == 1) {
            $project_ids = array_keys($projects);
            Auth::redirect('post.php?post_form=yes&project=' . $project_ids[0]);
        } else {
            $tpl->assign('projects', $projects);
        }
    }
}

$tpl->displayTemplate();
