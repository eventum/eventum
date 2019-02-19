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

use Eventum\Controller\Helper\MessagesHelper;
use Project;
use Round_Robin;
use User;

class RoundRobinController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/round_robin.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

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

    private function newAction(): void
    {
        $res = Round_Robin::insert();
        $map = [
            1 => [ev_gettext('Thank you, the round robin entry was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the round robin entry.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this round robin entry.'), MessagesHelper::MSG_ERROR],
            -3 => [ev_gettext('Please enter the message for this round robin entry.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $res = Round_Robin::update();
        $map = [
            1 => [ev_gettext('Thank you, the round robin entry was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the round robin entry information.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this round robin entry.'), MessagesHelper::MSG_ERROR],
            -3 => [ev_gettext('Please enter the message for this round robin entry.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        Round_Robin::remove();
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $info = Round_Robin::getDetails($get->getInt('id'));
        $this->tpl->assign('info', $info);
        $this->prj_id = $info['prr_prj_id'];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => Round_Robin::getList(),
                'project_list' => Project::getAll(),
            ]
        );

        if ($this->prj_id) {
            $user_options = User::getActiveAssocList($this->prj_id, User::ROLE_CUSTOMER);
            $this->tpl->assign('user_options', $user_options);
        }
    }
}
