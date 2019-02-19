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
use Time_Tracking;

class TimeTrackingController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/time_tracking.tpl.html';

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
        $post = $this->getRequest()->request;

        $res = Time_Tracking::insertCategory($this->prj_id, $post->get('title'));
        $map = [
            1 => [ev_gettext('Thank you, the time tracking category was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new time tracking category.'), MessagesHelper::MSG_INFO],
            -2 => [ev_gettext('Please enter the title for this new time tracking category.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Time_Tracking::updateCategory($this->prj_id, $post->getInt('id'), $post->get('title'));
        $map = [
            1 => [ev_gettext('Thank you, the time tracking category was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the time tracking category information.'), MessagesHelper::MSG_INFO],
            -2 => [ev_gettext('Please enter the title for this time tracking category.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;

        Time_Tracking::removeCategory($post->get('items'));
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Time_Tracking::getCategoryDetails($get->getInt('id')));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'project' => Project::getDetails($this->prj_id),
                'list' => Time_Tracking::getCategoryList($this->prj_id),
            ]
        );
    }
}
