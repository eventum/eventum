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
use Project;

class CategoriesController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/categories.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /** @var int */
    private $id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->get('prj_id') ?: $request->query->get('prj_id');
        $this->id = $request->query->get('id');
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'new') {
            $res = Category::insert();
            $this->updateResult($res);
        } elseif ($this->cat == 'update') {
            $res = Category::update();
            $this->updateResult($res);
        } elseif ($this->cat == 'delete') {
            Category::remove();
        }

        if ($this->cat == 'edit') {
            $this->tpl->assign('info', Category::getDetails($this->id));
        }
    }

    private function updateResult($res)
    {
        $this->tpl->assign('result', $res);
        $map = array(
            1 => array('Thank you, the category was updated successfully.', Misc::MSG_INFO),
            -1 => array('An error occurred while trying to update the category.', Misc::MSG_ERROR),
            -2 => array('Please enter the title for this category.', Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'list' => Category::getList($this->prj_id),
                'project' => Project::getDetails($this->prj_id),
            )
        );
    }
}
