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

use Misc;
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
        }
    }

    private function newAction()
    {
        $res = Priority::insert();
        $this->tpl->assign('result', $res);
        $map = array(
            1 => array(ev_gettext('Thank you, the priority was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the priority.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this new priority.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $res = Priority::update();
        $this->tpl->assign('result', $res);
        $map = array(
            1 => array(ev_gettext('Thank you, the priority was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the priority.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this priority.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        Priority::remove();
    }

    private function editAction()
    {
        $id = $this->getRequest()->query->getInt('id');
        $this->tpl->assign('info', Priority::getDetails($id));
    }

    private function changeRankAction()
    {
        $get = $this->getRequest()->query;
        Priority::changeRank($this->prj_id, $get->getInt('id'), $get->getInt('rank'));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'project' => Project::getDetails($this->prj_id),
                'list' => Priority::getList($this->prj_id),
            )
        );
    }
}
