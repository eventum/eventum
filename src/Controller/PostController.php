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
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat');
        $this->post_form = $request->query->get('post_form') === 'yes';
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat === 'report') {
            $this->reportAction();
        } elseif ($this->post_form) {
            $this->postFormAction();
        } else {
            $projects = $this->getAnonymousProjects();
            if (count($projects) === 1) {
                $project_ids = array_keys($projects);
                $this->redirect('post.php', ['post_form' => 'yes', 'project' => $project_ids[0]]);
            }
        }
    }

    private function reportAction(): void
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

    private function postFormAction(): void
    {
        $get = $this->getRequest()->query;
        $prj_id = $get->getInt('project');

        $projects = $this->getAnonymousProjects($prj_id);
        if (!$projects) {
            return;
        }

        // get list of custom fields for the selected project
        $options = Project::getAnonymousPostOptions($prj_id);
        $show_custom_fields = isset($options['show_custom_fields']) && $options['show_custom_fields'] === 'yes';

        if ($show_custom_fields) {
            $custom_fields = Custom_Field::getListByProject($prj_id, 'anonymous_form', false, true);
            $this->tpl->assign('custom_fields', $custom_fields);
        }

        $this->tpl->assign('project_name', Project::getName($prj_id));
    }

    /**
     * only list those projects that are allowing anonymous reporting of new issues
     *
     * @param array
     */
    private function getAnonymousProjects(?int $prj_id = null): array
    {
        $projects = Project::getAnonymousList();
        if (!$projects) {
            return [];
        }

        if ($prj_id && !array_key_exists($prj_id, $projects)) {
            return [];
        }

        return $projects;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $projects = $this->getAnonymousProjects();

        $this->tpl->assign(['projects' => $projects]);
    }
}
