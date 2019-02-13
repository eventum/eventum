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
use Severity;

class SeveritiesController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/severities.tpl.html';

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
        $post = $this->getRequest()->request;

        $res = Severity::insert($this->prj_id, $post->get('title'), $post->get('description'), $post->get('rank'));
        $map = [
            1 => ['Thank you, the severity was added successfully.', MessagesHelper::MSG_INFO],
            -1 => ['An error occurred while trying to add the severity.', MessagesHelper::MSG_ERROR],
            -2 => ['Please enter the title for this new severity.', MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Severity::update($post->get('id'), $post->get('title'), $post->get('description'), $post->get('rank'));
        $map = [
            1 => ['Thank you, the severity was added successfully.', MessagesHelper::MSG_INFO],
            -1 => ['An error occurred while trying to add the severity.', MessagesHelper::MSG_ERROR],
            -2 => ['Please enter the title for this new severity.', MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;

        Severity::remove($post->get('items'));
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Severity::getDetails($get->getInt('id')));
    }

    private function changeRankAction(): void
    {
        $get = $this->getRequest()->query;

        Severity::changeRank($this->prj_id, $get->getInt('id'), $get->getInt('rank'));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => Severity::getList($this->prj_id),
                'project' => Project::getDetails($this->prj_id),
            ]
        );
    }
}
