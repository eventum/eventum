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
use Link_Filter;
use Project;
use User;

class LinkFiltersController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/link_filters.tpl.html';

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
        $res = Link_Filter::insert();
        $map = [
            1 => [ev_gettext('Thank you, the link filter was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new link filter.'), MessagesHelper::MSG_INFO],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $res = Link_Filter::update();
        $map = [
            1 => [ev_gettext('Thank you, the link filter was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the link filter.'), MessagesHelper::MSG_INFO],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        $res = Link_Filter::remove();
        $map = [
            1 => [ev_gettext('Thank you, the link filter was deleted successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to delete the link filter.'), MessagesHelper::MSG_INFO],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $info = Link_Filter::getDetails($get->get('id'));
        $this->tpl->assign('info', $info);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => Link_Filter::getList(),
                'project_list' => Project::getAll(),
                'user_roles' => User::getRoles(),
            ]
        );
    }
}
