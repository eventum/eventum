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
use Group;
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
        } elseif ($this->cat == 'change_status') {
            $this->changeStatusAction();
        } elseif ($this->cat == 'edit') {
            $this->editAction();
        } else {
            $this->indexAction();
        }
    }

    private function newAction()
    {
        $res = User::insertFromPost();
        $map = [
            1 => [ev_gettext('Thank you, the user was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new user.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction()
    {
        $post = $this->getRequest()->request;

        $this->user_details = User::getDetails($post->getInt('id'));

        if (Auth::getCurrentRole() != User::ROLE_ADMINISTRATOR) {
            // don't let managers edit any users that have a role of administrator
            foreach ($this->user_details['roles'] as $prj_id => $role) {
                if ($role['pru_role'] == User::ROLE_ADMINISTRATOR) {
                    $this->error(ev_gettext('Sorry, you are not allowed to access this page.'));
                }
            }

            // don't let manager elevate the role of any user to administrator
            foreach ($_POST['role'] as $prj_id => $role) {
                if ($role >= User::ROLE_ADMINISTRATOR) {
                    $this->error(ev_gettext('Sorry, you cannot perform that action.'));
                }
            }
        }

        $res = User::updateFromPost();
        $map = [
            1 => [ev_gettext('Thank you, the user was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the user information.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);

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

        if (Auth::getCurrentRole() != User::ROLE_ADMINISTRATOR) {
            foreach ($this->user_details['roles'] as $prj_id => $role) {
                if ($role['pru_role'] == User::ROLE_ADMINISTRATOR) {
                    $this->error(ev_gettext('Sorry, you are not allowed to access this page.'));
                }
            }
        }

        $this->tpl->assign('info', $this->user_details);
    }

    private function indexAction()
    {
        $get = $this->getRequest()->query;

        $options = [
            'customers' => $get->get('show_customers', 0),
            'inactive' => $get->get('show_inactive'),
            'groups' => $get->get('show_groups'),
        ];
        $list = User::getList($options);

        // disable partners column if no user has data
        $options['partners'] = !!array_filter($this->matchField($list, 'usr_par_code'));
        $active_users = count(array_filter($this->matchField($list, 'usr_status', 'active')));

        $this->tpl->assign(
            [
                'list' => $list,
                'active_user_count' => $active_users,
                'list_options' => $options,
            ]
        );
    }

    /**
     * Iterate over list matching criteria
     *
     * @param array $list
     * @param string $field
     * @param string $value
     * @return array
     */
    private function matchField($list, $field, $value = null)
    {
        return array_map(
            function ($usr) use ($field, $value) {
                if ($value !== null) {
                    return $usr[$field] == $value;
                }

                return !empty($usr[$field]);
            }, $list
        );
    }

    private function getProjectRoles($project_list, $user_details)
    {
        $project_roles = [];
        foreach ($project_list as $prj_id => $prj_title) {
            $excluded_roles = [User::ROLE_CUSTOMER];
            if ($this->role_id == User::ROLE_MANAGER) {
                $excluded_roles[] = User::ROLE_ADMINISTRATOR;
            }
            if (isset($user_details['roles'][$prj_id])
                && $user_details['roles'][$prj_id]['pru_role'] == User::ROLE_CUSTOMER
            ) {
                // if user is already a customer, keep customer role in list
                unset($excluded_roles[array_search(User::ROLE_CUSTOMER, $excluded_roles)]);
            }
            if (isset($user_details['roles'][$prj_id])
                && $user_details['roles'][$prj_id]['pru_role'] == User::ROLE_ADMINISTRATOR
            ) {
                // if user is already an admin, keep admin role in list
                unset($excluded_roles[array_search(User::ROLE_ADMINISTRATOR, $excluded_roles)]);
            }
            $project_roles[$prj_id] = [0 => 'No Access'] + User::getRoles($excluded_roles);
        }

        return $project_roles;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $project_list = Project::getAll();

        $this->tpl->assign(
            [
                'cat' => $this->cat,
                'project_list' => $project_list,
                'project_roles' => $this->getProjectRoles($project_list, $this->user_details),
                'group_list' => Group::getAssocListAllProjects(),
                'partners' => $this->getPartnersList(),
            ]
        );
    }

    private function getPartnersList()
    {
        $partners = [];
        $backends = ExtensionManager::getManager()->getPartnerClasses();
        foreach ($backends as $par_code => $backend) {
            $partners[$par_code] = $backend->getName();
        }

        return $partners;
    }
}
