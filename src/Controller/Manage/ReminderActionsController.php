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
use Reminder;
use Reminder_Action;
use User;

class ReminderActionsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/reminder_actions.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $rem_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->rem_id = $request->request->getInt('rem_id') ?: $request->query->getInt('rem_id');
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
        } elseif ($this->cat == 'change_rank') {
            $this->changeRankAction();
        }
    }

    private function newAction(): void
    {
        $res = Reminder_Action::insert();
        $map = [
            1 => [ev_gettext('Thank you, the action was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new action.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new action.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
        $rem_id = $this->getRequest()->request->getInt('rem_id');
        $this->redirect("reminder_actions.php?rem_id={$rem_id}");
    }

    private function updateAction(): void
    {
        $res = Reminder_Action::update();
        $map = [
            1 => [ev_gettext('Thank you, the action was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the action.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this action.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
        $rem_id = $this->getRequest()->request->getInt('rem_id');
        $this->redirect("reminder_actions.php?rem_id={$rem_id}");
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;

        Reminder_Action::remove($post->get('items'));
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Reminder_Action::getDetails($get->getInt('id')));
    }

    private function changeRankAction(): void
    {
        $get = $this->getRequest()->query;

        Reminder_Action::changeRank($this->rem_id, $get->getInt('id'), $get->getInt('rank'));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $user_options = User::getActiveAssocList(Reminder::getProjectID($this->rem_id), User::ROLE_CUSTOMER);
        $this->tpl->assign(
            [
                'rem_id' => $this->rem_id,
                'rem_title' => Reminder::getTitle($this->rem_id),
                'action_types' => Reminder_Action::getActionTypeList(),
                'list' => Reminder_Action::getAdminList($this->rem_id),
                'user_options' => $user_options,
            ]
        );
    }
}
