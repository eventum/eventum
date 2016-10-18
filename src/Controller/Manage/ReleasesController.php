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
use Release;

class ReleasesController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/releases.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
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
        $res = Release::insert();
        $this->tpl->assign('result', $res);
        $map = [
            1 => [ev_gettext('Thank you, the release was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the release.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new release.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction()
    {
        $res = Release::update();
        $this->tpl->assign('result', $res);
        $map = [
            1 => [ev_gettext('Thank you, the release was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the release.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this release.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction()
    {
        Release::remove();
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Release::getDetails($get->getInt('id')));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            [
                'project' => Project::getDetails($this->prj_id),
                'list' => Release::getList($this->prj_id),
            ]
        );
    }
}
