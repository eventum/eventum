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
use Misc;
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

    private function newAction()
    {
        $res = Reminder::insert();
        $map = array(
            1 => array(ev_gettext('Thank you, the reminder was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new reminder.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this new reminder.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $res = Reminder::update();
        $map = array(
            1 => array(ev_gettext('Thank you, the reminder was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the reminder.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this reminder.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        Reminder::remove();
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $info = Reminder::getDetails($get->getInt('id'));
        if ($this->prj_id) {
            $info['rem_prj_id'] = $this->prj_id;
        }

        // only show customers and support levels if the selected project really needs it
        if ($crm = CRM::getInstance($info['rem_prj_id'])) {
            $this->crm = $crm;
            $this->tpl->assign(
                array(
                    'customers' => $crm->getCustomerAssocList(),
                    'support_levels' => $crm->getSupportLevelAssocList(),
                )
            );
        }

        // wouldn't make much sense to create a reminder for a 'Not Prioritized'
        // issue, so let's remove that as an option
        $priorities = array_flip(Priority::getAssocList($info['rem_prj_id']));
        unset($priorities['Not Prioritized']);

        $this->tpl->assign(
            array(
                'issues' => Reminder::getIssueAssocListByProject($info['rem_prj_id']),
                'info' => $info,
                'priorities' => array_flip($priorities),
                'severities', Severity::getAssocList($info['rem_prj_id']),
                'products' => Product::getAssocList(),
            )
        );
    }

    private function changeRankAction()
    {
        $get = $this->getRequest()->query;

        Reminder::changeRank($get->getInt('id'), $get->getInt('rank'));
    }

    private function infoAction()
    {
        $this->tpl->assign(
            array(
                'info' => array('rem_prj_id' => $this->prj_id),
                'issues' => Reminder::getIssueAssocListByProject($this->prj_id),
            )
        );

        // wouldn't make much sense to create a reminder for a 'Not Prioritized'
        // issue, so let's remove that as an option
        $priorities = array_flip(Priority::getAssocList($this->prj_id));
        unset($priorities['Not Prioritized']);

        $this->tpl->assign(
            array(
                'priorities' => array_flip($priorities),
                'severities' => Severity::getAssocList($this->prj_id),
                'products' => Product::getAssocList(),
            )
        );

        // only show customers and support levels if the selected project really needs it
        if ($crm = CRM::getInstance($this->prj_id)) {
            $this->crm = $crm;
            $this->tpl->assign(
                array(
                    'customers' => $crm->getCustomerAssocList(),
                    'support_levels' => $crm->getSupportLevelAssocList(),
                )
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'backend_uses_support_levels' => false,
                'project_has_customer_integration' => $this->crm != null,
                'project_list' => Project::getAll(),
                'list' => Reminder::getAdminList(),
            )
        );
    }
}
