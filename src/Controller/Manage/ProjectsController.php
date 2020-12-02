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
use DateTime;
use Display_Column;
use Eventum\Db\DatabaseException;
use Eventum\Db\Doctrine;
use Eventum\Extension\ExtensionManager;
use Eventum\Extension\Legacy\WorkflowLegacyExtension;
use Eventum\Extension\RegisterExtension;
use Eventum\Model\Entity;
use Eventum\Model\Repository\ProjectRepository;
use Eventum\ServiceContainer;
use Project;
use Status;
use Symfony\Component\HttpFoundation\ParameterBag;
use Time_Tracking;
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
    /** @var ProjectRepository */
    private $repo;

    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
        $this->repo = Doctrine::getProjectRepository();
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
        $post = $this->getRequest()->request;
        if (!$this->csrf->isValid('manage-projects', $post->get('token'))) {
            $this->error('Invalid CSRF Token');
        }

        if (Validation::isWhitespace($post->get('title'))) {
            $this->messages->addErrorMessage(ev_gettext('Please enter the title for this project.'));

            return;
        }

        $project = $this->updateFromRequest(new Entity\Project(), $post);
        $project->setCreatedDate(new DateTime());
        $project->setAnonymousPost('disabled');

        try {
            $this->repo->persistAndFlush($project);
        } catch (DatabaseException $e) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to add the new project.'));

            return;
        }

        $prj_id = $project->getId();
        // users who are now being associated with this project should be set to 'Standard User'
        $role_id = User::ROLE_USER;
        $lead_usr_id = $project->getLeadUserId();

        foreach ($post->get('users', []) as $usr_id) {
            $usr_id = (int)$usr_id;
            $isLeadUser = $usr_id === $lead_usr_id;
            Project::associateUser($prj_id, $usr_id, $isLeadUser ? User::ROLE_MANAGER : $role_id);
        }
        foreach ($post->get('statuses', []) as $sta_id) {
            Status::addProjectAssociation($sta_id, $prj_id);
        }
        Display_Column::setupNewProject($prj_id);

        // insert default timetracking categories
        Time_Tracking::addProjectDefaults($prj_id);

        $this->messages->addInfoMessage(ev_gettext('Thank you, the project was updated successfully.'));
        $this->redirect("projects.php?cat=edit&id={$prj_id}");
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
        $project = $this->updateFromRequest($this->repo->findOrCreate($prj_id), $post);
        try {
            $this->repo->persistAndFlush($project);
        } catch (DatabaseException $e) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to update the project information.'));

            return;
        }

        // enable WorkflowLegacyExtension if project has workflow enabled
        if ($project->getWorkflowBackend()) {
            $register = new RegisterExtension();
            $register->enable(WorkflowLegacyExtension::class);
        }

        Project::removeUserByProjects([$prj_id], $post->get('users'));
        // users who are now being associated with this project should be set to 'Standard User'
        $role_id = User::ROLE_USER;
        $lead_usr_id = $post->getInt('lead_usr_id');
        foreach ($post->get('users', []) as $usr_id) {
            $usr_id = (int)$usr_id;
            $isLeadUser = $usr_id === $lead_usr_id;
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
        $this->redirect("projects.php?cat=edit&id={$prj_id}");
    }

    private function editAction(): void
    {
        $prj_id = $this->getRequest()->query->getInt('id');
        $project = $this->repo->findById($prj_id);

        $details = $project->toArray();
        $details['prj_assigned_users'] = Project::getUserColList($prj_id);
        $details['assigned_statuses'] = array_keys(Status::getAssocStatusList($prj_id));

        $this->tpl->assign('info', $details);
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

    private function updateFromRequest(Entity\Project $project, ParameterBag $post): Entity\Project
    {
        return $project
            ->setTitle($post->get('title'))
            ->setStatus($post->get('status'))
            ->setLeadUserId($post->getInt('lead_usr_id'))
            ->setInitialStatusId($post->getInt('initial_status'))
            ->setOutgoingSenderName($post->get('outgoing_sender_name'))
            ->setOutgoingSenderEmail($post->get('outgoing_sender_email'))
            ->setSenderFlag($post->get('sender_flag'))
            ->setSenderFlagLocation($post->get('flag_location'))
            ->setMailAliases($post->get('mail_aliases'))
            ->setRemoteInvocation($post->get('remote_invocation'))
            ->setSegregateReporter($post->get('segregate_reporter') ? true : false)
            ->setCustomerBackend($post->get('customer_backend'))
            ->setWorkflowBackend($post->get('workflow_backend'));
    }
}
