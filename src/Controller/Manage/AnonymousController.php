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

use Category;
use Misc;
use Priority;
use Project;

class AnonymousController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/anonymous.tpl.html';

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
        if ($this->cat == 'update') {
            $this->updateAnonymousPostAction();
        }
    }

    private function updateAnonymousPostAction()
    {
        $res = Project::updateAnonymousPost($this->prj_id);
        $this->tpl->assign('result', $res);
        $map = array(
            1 => array(ev_gettext('Thank you, the information was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the information.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        // load the form fields
        $this->tpl->assign(
            array(
                'project' => Project::getDetails($this->prj_id),
                'cats' => Category::getAssocList($this->prj_id),
                'priorities' => Priority::getList($this->prj_id),
                'users' => Project::getUserAssocList($this->prj_id, 'active'),
                'options' => Project::getAnonymousPostOptions($this->prj_id),
                'prj_id' => $this->prj_id,
            )
        );
    }
}
