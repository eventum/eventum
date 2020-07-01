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

use Email_Response;
use Eventum\Controller\Helper\MessagesHelper;
use Project;

class EmailResponsesController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/email_responses.tpl.html';

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
        $res = Email_Response::insert();
        $map = [
            1 => [ev_gettext('Thank you, the email response was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new email response.'), MessagesHelper::MSG_INFO],
            -2 => [ev_gettext('Please enter the title for this new issue resolution.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages(
            $res,
            $map
        );
    }

    private function updateAction(): void
    {
        $res = Email_Response::update();
        $map = [
            1 => [ev_gettext('Thank you, the email response was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the new email response.'), MessagesHelper::MSG_INFO],
            -2 => [ev_gettext('Please enter the title for this issue resolution.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        Email_Response::remove();
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Email_Response::getDetails($get->get('id')));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'project_list' => Project::getAll(),
                'list' => Email_Response::getList(),
            ]
        );
    }
}
