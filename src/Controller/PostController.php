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

namespace Eventum\Controller;

use Custom_Field;
use Issue;
use Project;

class PostController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'post.tpl.html';

    /** @var string */
    private $cat;

    /** @var bool */
    private $post_form;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat');
        $this->post_form = $request->query->get('post_form') == 'yes';
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'report') {
            $this->reportAction();
        } elseif ($this->post_form) {
            $this->postFormAction();
        } else {
            $this->setupProjects();
        }
    }

    private function reportAction()
    {
        $iss_id = Issue::addAnonymousReport();

        if ($iss_id != -1) {
            // show direct links to the issue page, issue listing page and email listing page
            $this->tpl->assign('new_issue_id', $iss_id);
        } else {
            // need to show everything again
            $this->tpl->assign('error_msg', '1');
        }
    }

    private function postFormAction()
    {
        $get = $this->getRequest()->query;
        $prj_id = $get->getInt('project');

        // only list those projects that are allowing anonymous reporting of new issues
        $projects = $this->setupProjects($prj_id);
        if (!$projects) {
            return;
        }

        // get list of custom fields for the selected project
        $options = Project::getAnonymousPostOptions($prj_id);
        $show_custom_fields = isset($options['show_custom_fields']) && $options['show_custom_fields'] == 'yes';

        if ($show_custom_fields) {
            $custom_fields = Custom_Field::getListByProject($prj_id, 'anonymous_form', false, true);
            $this->tpl->assign('custom_fields', $custom_fields);
        }

        $this->tpl->assign('project_name', Project::getName($prj_id));
    }

    /**
     * only list those projects that are allowing anonymous reporting of new issues
     * @param int $prj_id
     */
    private function setupProjects($prj_id = null)
    {
        $projects = Project::getAnonymousList();
        if (!$projects) {
            return false;
        }

        if ($prj_id && !in_array($prj_id, array_keys($projects))) {
            $this->tpl->assign('no_projects', '1');

            return false;
        }

        if ($prj_id == null && count($projects) == 1) {
            $project_ids = array_keys($projects);
            $this->redirect('post.php', ['post_form' => 'yes', 'project' => $project_ids[0]]);
        }

        $this->tpl->assign('projects', $projects);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
