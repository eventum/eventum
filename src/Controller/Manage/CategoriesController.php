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
use Eventum\Controller\Helper\MessagesHelper;
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
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
        $this->id = $request->query->getInt('id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
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

    /**
     * @param int $res
     */
    private function updateResult($res): void
    {
        $this->tpl->assign('result', $res);
        $map = [
            1 => ['Thank you, the category was updated successfully.', MessagesHelper::MSG_INFO],
            -1 => ['An error occurred while trying to update the category.', MessagesHelper::MSG_ERROR],
            -2 => ['Please enter the title for this category.', MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => Category::getList($this->prj_id),
                'project' => Project::getDetails($this->prj_id),
            ]
        );
    }
}
