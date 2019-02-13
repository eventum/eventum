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

use CRM;
use Eventum\Controller\Helper\MessagesHelper;
use Priority;
use Product;
use Project;
use Reminder;
use Severity;

class RemindersController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/reminders.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /** @var CRM */
    private $crm;

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
        } elseif ($this->cat == 'change_rank') {
            $this->changeRankAction();
        } elseif ($this->prj_id) {
            $this->infoAction();
        }
    }

    private function newAction(): void
    {
        $res = Reminder::insert();
        $map = [
            1 => [ev_gettext('Thank you, the reminder was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new reminder.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new reminder.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
        $prj_id = $this->getRequest()->request->getInt('project');
        $this->redirect("reminders.php?prj_id={$prj_id}");
    }

    private function updateAction(): void
    {
        $res = Reminder::update();
        $map = [
            1 => [ev_gettext('Thank you, the reminder was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the reminder.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this reminder.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
        $prj_id = $this->getRequest()->request->getInt('project');
        $this->redirect("reminders.php?prj_id={$prj_id}");
    }

    private function deleteAction(): void
    {
        Reminder::remove();
    }

    private function changeRankAction(): void
    {
        $get = $this->getRequest()->query;

        Reminder::changeRank($get->getInt('id'), $get->getInt('rank'));
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $info = Reminder::getDetails($get->getInt('id'));
        if ($this->prj_id) {
            $info['rem_prj_id'] = $this->prj_id;
        }

        $this->tpl->assign(
            [
                'info' => $info,
            ]
        );
        $this->setProjectData($info['rem_prj_id']);
    }

    private function infoAction(): void
    {
        $this->tpl->assign(
            [
                'info' => ['rem_prj_id' => $this->prj_id],
            ]
        );

        $this->setProjectData($this->prj_id);
    }

    /**
     * Common code for infoAction and editAction
     *
     * @param int $prj_id
     */
    private function setProjectData($prj_id): void
    {
        $this->tpl->assign(
            [
                'issues' => Reminder::getIssueAssocListByProject($prj_id),
                'priorities' => $this->getPriorities($prj_id),
                'severities' => Severity::getAssocList($prj_id),
                'products' => Product::getAssocList(),
            ]
        );

        // only show customers and support levels if the selected project really needs it
        if ($crm = CRM::getInstance($prj_id)) {
            $this->crm = $crm;
            $this->tpl->assign(
                [
                    'customers' => $crm->getCustomerAssocList(),
                    'support_levels' => $crm->getSupportLevelAssocList(),
                ]
            );
        }
    }

    /**
     * Get Issue Priorities to use for reminders
     *
     * @param int $prj_id
     * @return array
     */
    private function getPriorities($prj_id)
    {
        $priorities = Priority::getAssocList($prj_id);

        // wouldn't make much sense to create a reminder for a 'Not Prioritized'
        // issue, so let's remove that as an option
        // TODO: use array_search instead of array_flip x2
        $reveresed = array_flip($priorities);
        unset($reveresed['Not Prioritized']);
        $priorities = array_flip($reveresed);

        return $priorities;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'backend_uses_support_levels' => false,
                'project_has_customer_integration' => $this->crm != null,
                'project_list' => Project::getAll(),
                'list' => Reminder::getAdminList(),
            ]
        );
    }
}
