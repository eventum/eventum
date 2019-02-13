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
use FAQ;
use Project;

class FaqController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/faq.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var string */
    private $cat;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->query->getInt('prj_id');
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
        } elseif ($this->prj_id) {
            $this->infoAction();
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        } elseif ($this->cat == 'change_rank') {
            $this->changeRankAction();
        }
    }

    private function newAction(): void
    {
        $res = FAQ::insert();
        $map = [
            1 => [ev_gettext('Thank you, the FAQ entry was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the FAQ entry.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this FAQ entry.'), MessagesHelper::MSG_ERROR],
            -3 => [ev_gettext('Please enter the message for this FAQ entry.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $res = FAQ::update();
        $map = [
            1 => [ev_gettext('Thank you, the FAQ entry was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the FAQ entry information.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this FAQ entry.'), MessagesHelper::MSG_ERROR],
            -3 => [ev_gettext('Please enter the message for this FAQ entry.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        FAQ::remove();
    }

    private function editAction(): void
    {
        $info = FAQ::getDetails($_GET['id']);
        if ($this->prj_id) {
            $info['faq_prj_id'] = $this->prj_id;
        }
        if (CRM::hasCustomerIntegration($info['faq_prj_id'])) {
            $crm = CRM::getInstance($info['faq_prj_id']);
            $this->tpl->assign('support_levels', $crm->getSupportLevelAssocList());
        }
        $this->tpl->assign('info', $info);
    }

    private function changeRankAction(): void
    {
        $get = $this->getRequest()->query;
        FAQ::changeRank($get->get('id'), $get->get('rank'));
    }

    private function infoAction(): void
    {
        $this->tpl->assign('info', ['faq_prj_id' => $this->prj_id]);
        if ($crm = CRM::getInstance($this->prj_id)) {
            $this->tpl->assign('support_levels', $crm->getSupportLevelAssocList());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => FAQ::getList(),
                'project_list' => Project::getAll(),
            ]
        );
    }
}
