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

use Misc;
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
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->rem_id = $request->request->getInt('rem_id') ?: $request->query->getInt('rem_id');
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
        } elseif ($this->cat == 'delete') {
            $this->deleteAction();
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        } elseif ($this->cat == 'change_rank') {
            $this->changeRankAction();
        }
    }

    private function newAction()
    {
        $res = Reminder_Action::insert();
        $map = array(
            1 => array(ev_gettext('Thank you, the action was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new action.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this new action.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $res = Reminder_Action::update();
        $map = array(
            1 => array(ev_gettext('Thank you, the action was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the action.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this action.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        $post = $this->getRequest()->request;

        Reminder_Action::remove($post->get('items'));
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Reminder_Action::getDetails($get->getInt('id')));
    }

    private function changeRankAction()
    {
        $get = $this->getRequest()->query;

        Reminder_Action::changeRank($this->rem_id, $get->getInt('id'), $get->getInt('rank'));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $user_options = User::getActiveAssocList(Reminder::getProjectID($this->rem_id), User::ROLE_CUSTOMER);
        $this->tpl->assign(
            array(
                'rem_id' => $this->rem_id,
                'rem_title' => Reminder::getTitle($this->rem_id),
                'action_types' => Reminder_Action::getActionTypeList(),
                'list' => Reminder_Action::getAdminList($this->rem_id),
                'user_options' => $user_options,
            )
        );
    }
}
