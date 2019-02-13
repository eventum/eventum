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
use Group;
use Project;
use User;

class GroupsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/groups.tpl.html';

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat == 'new') {
            $this->newAction();
        } elseif ($this->cat == 'update') {
            $this->updateAction();
        } elseif ($this->cat == 'delete') {
            $this->deleteAction();
        }
    }

    private function newAction(): void
    {
        $res = Group::insert();
        $map = [
            1 => [ev_gettext('Thank you, the group was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new group.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $res = Group::update();
        $map = [
            1 => [ev_gettext('Thank you, the group was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the group.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        Group::remove();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $prj_id = Auth::getCurrentProject();

        if ($this->cat == 'edit') {
            $id = $this->getRequest()->query->get('id');
            $info = Group::getDetails($id);
        } else {
            $info = null;
        }

        $this->tpl->assign(
            [
                'user_options' => User::getActiveAssocList($prj_id, User::ROLE_CUSTOMER, true),
                'list' => Group::getList(),
                'project_list' => Project::getAll(),
                'info' => $info,
            ]
        );
    }
}
