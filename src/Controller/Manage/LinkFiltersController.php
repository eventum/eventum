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

use Link_Filter;
use Misc;
use Project;
use User;

class LinkFiltersController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/link_filters.tpl.html';

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
        $res = Link_Filter::insert();
        $map = array(
            1 => array(ev_gettext('Thank you, the link filter was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new link filter.'), Misc::MSG_INFO),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $res = Link_Filter::update();
        $map = array(
            1 => array(ev_gettext('Thank you, the link filter was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the link filter.'), Misc::MSG_INFO),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        $res = Link_Filter::remove();
        $map = array(
            1 => array(ev_gettext('Thank you, the link filter was deleted successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to delete the link filter.'), Misc::MSG_INFO),
        );
        Misc::mapMessages($res, $map);
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $info = Link_Filter::getDetails($get->get('id'));
        $this->tpl->assign('info', $info);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'list' => Link_Filter::getList(),
                'project_list' => Project::getAll(),
                'user_roles' => User::getRoles(),
            )
        );
    }
}
