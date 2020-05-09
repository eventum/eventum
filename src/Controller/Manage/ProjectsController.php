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
use Eventum\Db\DatabaseException;
use Eventum\Extension\ExtensionManager;
use Eventum\ServiceContainer;
use Project;
use Status;
use User;
use Validation;

class ProjectsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/projects.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
    }

    protected function defaultAction(): void
    {
        if ($this->cat === 'new') {
            $this->newAction();
        } elseif ($this->cat === 'update') {
            $this->updateAction();
        } elseif ($this->cat === 'edit') {
            $this->editAction();
        }
    }

    private function newAction(): void
    {
        if (!$this->csrf->isValid('manage-projects', $this->getRequest()->request->get('token'))) {
            $this->error('Invalid CSRF Token');
        }
        $map = [
            1 => [ev_gettext('Thank you, the project was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new project.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new project.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages(Project::insert(), $map);
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        if (!$this->csrf->isValid('manage-projects', $post->get('token'))) {
            $this->error('Invalid CSRF Token');
        }

        if (Validation::isWhitespace($post->get('title'))) {
            $this->messages->addErrorMessage(ev_gettext('Please enter the title for this project.'));

            return;
        }

        $prj_id = $post->getInt('id');

        $stmt = 'UPDATE
                    `project`
                 SET
                    prj_title=?,
                    prj_status=?,
                    prj_lead_usr_id=?,
                    prj_initial_sta_id=?,
                    prj_outgoing_sender_name=?,
                    prj_outgoing_sender_email=?,
                    prj_sender_flag=?,
                    prj_sender_flag_location=?,
                    prj_mail_aliases=?,
                    prj_remote_invocation=?,
                    prj_segregate_reporter=?,
                    prj_customer_backend=?,
                    prj_workflow_backend=?
                 WHERE
                    prj_id=?';
        try {
            $this->db->query($stmt, [
                $post->get('title'),
                $post->get('status'),
                $post->get('lead_usr_id'),
                $post->get('initial_status'),
                $post->get('outgoing_sender_name'),
                $post->get('outgoing_sender_email'),
                $post->get('sender_flag'),
                $post->get('flag_location'),
                $post->get('mail_aliases'),
                $post->get('remote_invocation'),
                $post->get('segregate_reporter'),
                $post->get('customer_backend'),
                $post->get('workflow_backend'),
                $prj_id,
            ]);
        } catch (DatabaseException $e) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to update the project information.'));

            return;
        }

        Project::removeUserByProjects([$prj_id], $post->get('users'));
        // users who are now being associated with this project should be set to 'Standard User'
        $role_id = User::ROLE_USER;
        $lead_usr_id = $post->getInt('lead_usr_id');
        foreach ($post->get('users') as $usr_id) {
            $isLeadUser = (int)$usr_id === $lead_usr_id;
            Project::associateUser($prj_id, $usr_id, $isLeadUser ? User::ROLE_MANAGER : $role_id);
        }

        $statuses = array_keys(Status::getAssocStatusList($prj_id));
        if (count($statuses) > 0) {
            Status::removeProjectAssociations($statuses, $prj_id);
        }
        foreach ($post->get('statuses') as $sta_id) {
            Status::addProjectAssociation($sta_id, $prj_id);
        }

        $this->messages->addInfoMessage(ev_gettext('Thank you, the project was updated successfully.'));
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Project::getDetails($get->getInt('id')));
    }

    protected function prepareTemplate(): void
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
                'csrf_token' => $this->csrf->getToken('manage-projects'),
            ]
        );
    }

    private function getWorkflowBackends(): array
    {
        // load classes from extension manager
        /** @var ExtensionManager $em */
        $em = ServiceContainer::get(ExtensionManager::class);
        $backends = $em->getWorkflowClasses();

        return $this->filterValues($backends);
    }

    private function getCustomerBackends(): array
    {
        // load classes from extension manager
        /** @var ExtensionManager $em */
        $em = ServiceContainer::get(ExtensionManager::class);
        $backends = $em->getCustomerClasses();

        return $this->filterValues($backends);
    }

    /**
     * Create array with key,value from $values $key,
     * i.e discarding values.
     */
    private function filterValues(iterable $values): array
    {
        $res = [];
        foreach ($values as $key => $value) {
            $res[$key] = $key;
        }

        return $res;
    }
}
