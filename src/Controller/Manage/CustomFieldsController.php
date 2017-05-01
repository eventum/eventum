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
use Custom_Field;
use Eventum\Controller\Helper\MessagesHelper;
use Project;
use User;

class CustomFieldsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/custom_fields.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
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
        } elseif ($this->cat == 'delete') {
            $this->deleteAction();
        } elseif ($this->cat == 'change_rank') {
            $this->changeRankAction();
        }

        if ($this->cat == 'edit') {
            $id = $this->getRequest()->query->get('id');
            $this->tpl->assign('info', Custom_Field::getDetails($id));
        }
    }

    private function newAction()
    {
        $res = Custom_Field::insert();
        $map = [
            1 => [ev_gettext('Thank you, the custom field was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new custom field.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction()
    {
        $res = Custom_Field::update();
        $this->messages->mapMessages(
            $res, [
                1 => [ev_gettext('Thank you, the custom field was updated successfully.'), MessagesHelper::MSG_INFO],
                -1 => [ev_gettext('An error occurred while trying to update the custom field information.'), MessagesHelper::MSG_ERROR],
            ]
        );
        $this->redirect(APP_RELATIVE_URL . 'manage/custom_fields.php');
    }

    private function deleteAction()
    {
        $res = Custom_Field::remove();
        $map = [
            true => [ev_gettext('Thank you, the custom field was removed successfully.'), MessagesHelper::MSG_INFO],
            false => [ev_gettext('An error occurred while trying to remove the custom field information.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function changeRankAction()
    {
        Custom_Field::changeRank();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $excluded_roles = [];
        if (!CRM::hasCustomerIntegration(Auth::getCurrentProject())) {
            $excluded_roles[] = User::ROLE_CUSTOMER;
        }
        $user_roles = User::getRoles($excluded_roles);
        $user_roles[9] = ev_gettext('Never Display');

        $this->tpl->assign(
            [
                'project_list' => Project::getAll(),
                'list' => Custom_Field::getList(),
                'user_roles' => $user_roles,
                'backend_list' => Custom_Field::getBackendList(),
                'order_by_list' => Custom_Field::$order_by_choices,
            ]
        );
    }
}
