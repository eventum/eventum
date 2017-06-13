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
use Eventum\Controller\Helper\MessagesHelper;
use Eventum\Extension\ExtensionManager;
use Project;
use Status;
use User;

class ProjectsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/projects.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'new') {
            $this->newAction();
        } elseif ($this->cat == 'update') {
            $this->updateAction();
        } elseif ($this->cat == 'edit') {
            $this->editAction();
        }
    }

    private function newAction()
    {
        $map = [
            1 => [ev_gettext('Thank you, the project was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new project.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new project.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages(Project::insert(), $map);
    }

    private function updateAction()
    {
        $map = [
            1 => [ev_gettext('Thank you, the project was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the project information.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this project.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages(Project::update(), $map);
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Project::getDetails($get->getInt('id')));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $usr_id = Auth::getUserID();
        $this->tpl->assign(
            [
                'active_projects' => Project::getAssocList($usr_id, true),
                'list' => Project::getList(),
                'user_options' => User::getActiveAssocList(),
                'status_options' => Status::getAssocList(),
                'customer_backends' => $this->getCustomerBackends(),
                'workflow_backends' => $this->getWorkflowBackends(),
            ]
        );
    }

    private function getWorkflowBackends()
    {
        // load classes from extension manager
        $manager = ExtensionManager::getManager();
        $backends = $manager->getWorkflowClasses();

        return $this->filterValues($backends);
    }

    private function getCustomerBackends()
    {
        // load classes from extension manager
        $manager = ExtensionManager::getManager();
        $backends = $manager->getCustomerClasses();

        return $this->filterValues($backends);
    }

    /**
     * Create array with key,value from $values $key,
     * i.e discarding values.
     */
    private function filterValues($values)
    {
        $res = [];
        foreach ($values as $key => $value) {
            $res[$key] = $key;
        }

        return $res;
    }
}
