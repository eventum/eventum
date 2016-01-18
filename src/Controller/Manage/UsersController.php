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

use Group;
use Misc;
use Partner;
use Project;
use User;

class UsersController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/users.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /** @var array */
    private $user_details;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->get('prj_id') ?: $request->query->get('prj_id');
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
        } elseif ($this->cat == 'change_status') {
            $this->changeStatusAction();
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        }
    }

    private function newAction()
    {
        $res = User::insertFromPost();
        $map = array(
            1 => array(ev_gettext('Thank you, the user was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new user.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $post = $this->getRequest()->request;

        $res = User::updateFromPost();
        $map = array(
            1 => array(ev_gettext('Thank you, the user was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the user information.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);

        $usr_id = $post->getInt('id');
        $this->redirect("users.php?cat=edit&id={$usr_id}");
    }

    private function changeStatusAction()
    {
        $post = $this->getRequest()->request;

        User::changeStatus($post->get('items'), $post->get('status'));
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $this->user_details = User::getDetails($get->getInt('id'));
        $this->tpl->assign('info', $this->user_details);
    }

    private function getProjectRoles($project_list, $user_details)
    {
        $project_roles = array();
        foreach ($project_list as $prj_id => $prj_title) {
            $excluded_roles = array(User::ROLE_CUSTOMER);
            if ($this->role_id == User::ROLE_MANAGER) {
                $excluded_roles[] = User::ROLE_ADMINISTRATOR;
            }
            if ($user_details['roles'][$prj_id]['pru_role'] == User::ROLE_CUSTOMER) {
                // if user is already a customer, keep customer role in list
                unset($excluded_roles[array_search(User::ROLE_CUSTOMER, $excluded_roles)]);
            }
            if ($user_details['roles'][$prj_id]['pru_role'] == User::ROLE_ADMINISTRATOR) {
                // if user is already an admin, keep admin role in list
                unset($excluded_roles[array_search(User::ROLE_ADMINISTRATOR, $excluded_roles)]);
            }
            $project_roles[$prj_id] = array(0 => 'No Access') + User::getRoles($excluded_roles);
        }

        return $project_roles;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $get = $this->getRequest()->query;
        $project_list = Project::getAll();

        $show_customer = $get->get('show_customers');
        $show_inactive = $get->get('show_inactive');
        $this->tpl->assign(
            array(
                'list' => User::getList($show_customer, $show_inactive),
                'project_list' => $project_list,
                'project_roles' => $this->getProjectRoles($project_list, $this->user_details),
                'group_list' => Group::getAssocListAllProjects(),
                'partners' => Partner::getAssocList(),
            )
        );
    }
}
