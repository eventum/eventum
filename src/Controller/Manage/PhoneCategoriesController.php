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
use Phone_Support;
use Project;

class PhoneCategoriesController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/phone_categories.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
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
        $res = Phone_Support::insertCategory();
        $this->tpl->assign('result', $res);
        $map = [
            1 => [ev_gettext('Thank you, the phone category was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the phone category.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new phone category.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $res = Phone_Support::updateCategory();
        $this->tpl->assign('result', $res);
        $map = [
            1 => [ev_gettext('Thank you, the phone category was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to uodate the phone category.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this phone category.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        Phone_Support::removeCategory();
    }

    private function editAction(): void
    {
        $id = $this->getRequest()->query->getInt('id');
        $this->tpl->assign('info', Phone_Support::getCategoryDetails($id));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'project' => Project::getDetails($this->prj_id),
                'list' => Phone_Support::getCategoryList($this->prj_id),
            ]
        );
    }
}
