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
use Misc;
use Project;

class EmailResponsesController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/email_responses.tpl.html';

    /** @var string */
    private $cat;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
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
        }
    }

    private function newAction()
    {
        $res = Email_Response::insert();
        $map = array(
            1 => array(ev_gettext('Thank you, the email response was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new email response.'), Misc::MSG_INFO),
            -2 => array(ev_gettext('Please enter the title for this new issue resolution.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map
        );
    }

    private function updateAction()
    {
        $res = Email_Response::update();
        $map = array(
            1 => array(ev_gettext('Thank you, the email response was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the new email response.'), Misc::MSG_INFO),
            -2 => array(ev_gettext('Please enter the title for this issue resolution.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        Email_Response::remove();
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Email_Response::getDetails($get->get('id')));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'project_list' => Project::getAll(),
                'list' => Email_Response::getList(),
            )
        );
    }
}
