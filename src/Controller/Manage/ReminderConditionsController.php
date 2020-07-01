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

use Category;
use Eventum\Controller\Helper\MessagesHelper;
use Group;
use Reminder;
use Reminder_Action;
use Reminder_Condition;
use Status;

class ReminderConditionsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/reminder_conditions.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $rem_id;

    /** @var int */
    private $rma_id;

    /** @var int */
    private $field;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->rem_id = $request->request->getInt('rem_id') ?: $request->query->getInt('rem_id');
        $this->rma_id = $request->request->getInt('rma_id') ?: $request->query->getInt('rma_id');
        $this->field = $request->query->getInt('field');
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
        $res = Reminder_Condition::insert();
        $map = [
            1 => [ev_gettext('Thank you, the condition was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new condition.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new condition.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
        $rem_id = $this->getRequest()->request->getInt('rem_id');
        $rma_id = $this->getRequest()->request->getInt('rma_id');
        $this->redirect("reminder_conditions.php?rem_id={$rem_id}&rma_id={$rma_id}");
    }

    private function updateAction(): void
    {
        $res = Reminder_Condition::update();
        $map = [
            1 => [ev_gettext('Thank you, the condition was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the condition.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this condition.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
        $rem_id = $this->getRequest()->request->getInt('rem_id');
        $rma_id = $this->getRequest()->request->getInt('rma_id');
        $this->redirect("reminder_conditions.php?rem_id={$rem_id}&rma_id={$rma_id}");
    }

    private function deleteAction(): void
    {
        Reminder_Condition::remove();
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $info = Reminder_Condition::getDetails($get->getInt('id'));
        if ($this->field) {
            $info['rlc_rmf_id'] = $this->field;
        } else {
            $this->field = $info['rlc_rmf_id'];
        }
        $this->tpl->assign('info', $info);
    }

    private function fieldOptions(): void
    {
        if (Reminder_Condition::canFieldBeCompared($this->field)) {
            $this->tpl->assign(
                [
                    'show_field_options' => 'yes',
                    'comparable_fields' => Reminder_Condition::getFieldAdminList(true),
                ]
            );

            return;
        }

        $prj_id = Reminder::getProjectID($this->rem_id);
        $field_title = strtolower(Reminder_Condition::getFieldTitle($this->field));

        if ($field_title == 'status') {
            $this->tpl->assign(
                [
                    'show_status_options' => 'yes',
                    'statuses' => Status::getAssocStatusList($prj_id),
                ]
            );

            return;
        }

        if ($field_title == 'category') {
            $this->tpl->assign(
                [
                    'show_category_options' => 'yes',
                    'categories' => Category::getAssocList($prj_id),
                ]
            );

            return;
        }

        if ($field_title == 'group' || $field_title == 'active group') {
            $this->tpl->assign(
                [
                    'show_group_options' => 'yes',
                    'groups' => Group::getAssocList($prj_id),
                ]
            );

            return;
        }

        $this->tpl->assign('show_status_options', 'no');
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        if ($this->field) {
            $this->fieldOptions();

            if ($this->cat != 'edit') {
                $this->tpl->assign(
                    'info',
                    [
                        'rlc_rmf_id' => $this->field,
                        'rlc_rmo_id' => '',
                        'rlc_value' => '',
                    ]
                );
            }
        }

        $this->tpl->assign(
            [
                'rem_id' => $this->rem_id,
                'rma_id' => $this->rma_id,
                'rem_title' => Reminder::getTitle($this->rem_id),
                'rma_title' => Reminder_Action::getTitle($this->rma_id),
                'fields' => Reminder_Condition::getFieldAdminList(),
                'operators' => Reminder_Condition::getOperatorAdminList(),
                'list' => Reminder_Condition::getAdminList($this->rma_id),
            ]
        );
    }
}
