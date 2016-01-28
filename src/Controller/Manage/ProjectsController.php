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

namespace Eventum\Controller\Manage;

use Auth;
use CRM;
use Misc;
use Project;
use Status;
use User;
use Workflow;

class ProjectsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/projects.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'new') {
            $this->newAction();
        } elseif ($this->cat == 'update') {
            $this->updateAction();
        } elseif ($this->cat == 'delete') {
            $this->deleteAction();
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        }
    }

    private function newAction()
    {
        $map = array(
            1 => array(ev_gettext('Thank you, the project was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new project.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this new project.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages(Project::insert(), $map);
    }

    private function updateAction()
    {
        $map = array(
            1 => array(ev_gettext('Thank you, the project was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the project information.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this project.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages(Project::update(), $map);
    }

    private function deleteAction()
    {
        $map = array(
            1 => array(ev_gettext('Thank you, the project was deleted successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to delete the project.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages(Project::remove(), $map);
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Project::getDetails($get->getInt('id')));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $usr_id = Auth::getUserID();
        $this->tpl->assign(
            array(
                'active_projects' => Project::getAssocList($usr_id, true),
                'list' => Project::getList(),
                'user_options' => User::getActiveAssocList(),
                'status_options' => Status::getAssocList(),
                'customer_backends' => CRM::getBackendList(),
                'workflow_backends' => Workflow::getBackendList(),
            )
        );
    }
}
