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

use Eventum\Extension\ExtensionManager;
use Eventum\ServiceContainer;
use Exception;
use Group;
use Project;
use Symfony\Component\HttpFoundation\ParameterBag;
use User;

class UsersController extends ManageBaseController
{
    private const CSRF_TOKEN_NAME = 'manage-users';

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
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->cat) {
            case 'new':
                $this->newAction();
                break;
            case 'update':
                $this->updateAction();
                break;
            case 'change_status':
                $this->changeStatusAction();
                break;
            case 'edit':
                $this->editAction();
                break;
            case 'unlock':
                $this->unlockAction();
                break;
            default:
                $this->fallbackAction();
        }
    }

    private function newAction(): void
    {
        $this->validateCsrf();

        $post = $this->getRequest()->request;
        $user = $this->getUserFromPost($post);

        try {
            $usr_id = User::insert($user);
        } catch (Exception $e) {
            $message = ev_gettext('An error occurred while trying to add the new user.');
            $this->messages->addErrorMessage($message);

            return;
        }

        $message = ev_gettext('Thank you, the user was added successfully.');
        $this->messages->addInfoMessage($message);

        $this->redirect("users.php?cat=edit&id={$usr_id}");
    }

    private function updateAction(): void
    {
        $this->validateCsrf();

        $post = $this->getRequest()->request;
        $this->user_details = User::getDetails($post->getInt('id'));

        if ($this->role_id != User::ROLE_ADMINISTRATOR) {
            // don't let managers edit any users that have a role of administrator
            foreach ($this->user_details['roles'] as $prj_id => $role) {
                if ($role['pru_role'] == User::ROLE_ADMINISTRATOR) {
                    $this->error(ev_gettext('Sorry, you are not allowed to access this page.'));
                }
            }

            // don't let manager elevate the role of any user to administrator
            foreach ($post->get('role') as $prj_id => $role) {
                if ($role >= User::ROLE_ADMINISTRATOR) {
                    $this->error(ev_gettext('Sorry, you cannot perform that action.'));
                }
            }
        }

        $usr_id = $post->getInt('id');
        $user = $this->getUserFromPost($post);

        try {
            User::update($usr_id, $user);
            $message = ev_gettext('Thank you, the user was updated successfully.');
            $this->messages->addInfoMessage($message);
        } catch (Exception $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to update the user information.');
            $this->messages->addErrorMessage($message);
        }

        $this->redirect("users.php?cat=edit&id={$usr_id}");
    }

    private function changeStatusAction(): void
    {
        $this->validateCsrf();

        $post = $this->getRequest()->request;
        User::changeStatus($post->get('items'), $post->get('status'));
    }

    private function unlockAction(): void
    {
        $get = $this->getRequest()->query;
        $usr_id = $get->getInt('id');

        try {
            User::unlock($usr_id);
            $message = ev_gettext('Thank you, user was unlocked successfully.');
            $this->messages->addInfoMessage($message);
        } catch (Exception $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to unlock the user.');
            $this->messages->addErrorMessage($message);
        }

        $this->redirect("users.php?cat=edit&id={$usr_id}");
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $this->user_details = User::getDetails($get->getInt('id'));

        if ($this->role_id !== User::ROLE_ADMINISTRATOR) {
            foreach ($this->user_details['roles'] as $prj_id => $role) {
                if ($role['pru_role'] == User::ROLE_ADMINISTRATOR) {
                    $this->error(ev_gettext('Sorry, you are not allowed to access this page.'));
                }
            }
        }

        $this->tpl->assign('info', $this->user_details);
    }

    private function fallbackAction(): void
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
    private function matchField($list, $field, $value = null): array
    {
        return array_map(
            function ($usr) use ($field, $value) {
                if ($value !== null) {
                    return $usr[$field] == $value;
                }

                return !empty($usr[$field]);
            },
            $list
        );
    }

    private function getProjectRoles($project_list, $user_details): array
    {
        $project_roles = [];
        foreach ($project_list as $prj_id => $prj_title) {
            $excluded_roles = [User::ROLE_CUSTOMER];
            if ($this->role_id === User::ROLE_MANAGER) {
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
    protected function prepareTemplate(): void
    {
        $project_list = Project::getAll();

        $this->tpl->assign(
            [
                'cat' => $this->cat,
                'project_list' => $project_list,
                'project_roles' => $this->getProjectRoles($project_list, $this->user_details),
                'group_list' => Group::getAssocListAllProjects(),
                'partners' => $this->getPartnersList(),
                'csrf_token' => $this->csrf->getToken(self::CSRF_TOKEN_NAME),
            ]
        );
    }

    private function getPartnersList(): array
    {
        $partners = [];
        /** @var ExtensionManager $em */
        $em = ServiceContainer::get(ExtensionManager::class);
        $backends = $em->getPartnerClasses();
        foreach ($backends as $par_code => $backend) {
            $partners[$par_code] = $backend->getName();
        }

        return $partners;
    }

    private function getUserFromPost(ParameterBag $post): array
    {
        $user = [
            'password' => $post->get('password'),
            'full_name' => $post->get('full_name'),
            'email' => $post->get('email'),
            'role' => $post->get('role'),
        ];

        if ($post->has('par_code')) {
            $user['par_code'] = $post->get('par_code');
        }

        if ($post->has('groups')) {
            $user['groups'] = $post->get('groups');
        } else {
            $user['groups'] = [];
        }

        return $user;
    }

    private function validateCsrf(): void
    {
        $token = $this->getRequest()->request->get('token');
        if (!$this->csrf->isValid(self::CSRF_TOKEN_NAME, $token)) {
            $this->error('Invalid CSRF Token');
        }
    }
}
