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
use Priority;
use Project;

class PrioritiesController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/priorities.tpl.html';

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
        } elseif ($this->cat == 'change_rank') {
            $this->changeRankAction();
        }
    }

    private function newAction(): void
    {
        $res = Priority::insert();
        $this->tpl->assign('result', $res);
        $map = [
            1 => [ev_gettext('Thank you, the priority was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the priority.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new priority.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $res = Priority::update();
        $this->tpl->assign('result', $res);
        $map = [
            1 => [ev_gettext('Thank you, the priority was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the priority.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this priority.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        Priority::remove();
    }

    private function editAction(): void
    {
        $id = $this->getRequest()->query->getInt('id');
        $this->tpl->assign('info', Priority::getDetails($id));
    }

    private function changeRankAction(): void
    {
        $get = $this->getRequest()->query;
        Priority::changeRank($this->prj_id, $get->getInt('id'), $get->getInt('rank'));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'project' => Project::getDetails($this->prj_id),
                'list' => Priority::getList($this->prj_id),
            ]
        );
    }
}
