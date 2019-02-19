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
use News;
use Project;

class NewsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/news.tpl.html';

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
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
        $res = News::insert();
        $map = [
            1 => [ev_gettext('Thank you, the news entry was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the news entry.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this news entry.'), MessagesHelper::MSG_ERROR],
            -3 => [ev_gettext('Please enter the message for this news entry.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $res = News::update();
        $map = [
            1 => [ev_gettext('Thank you, the news entry was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the news entry.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this news entry.'), MessagesHelper::MSG_ERROR],
            -3 => [ev_gettext('Please enter the message for this news entry.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        News::remove();
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', News::getAdminDetails($get->get('id')));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => News::getList(),
                'project_list' => Project::getAll(),
            ]
        );
    }
}
