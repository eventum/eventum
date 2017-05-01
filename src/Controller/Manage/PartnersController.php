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
use Partner;
use Project;

class PartnersController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/partners.tpl.html';

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'update') {
            $this->updateAction();
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        }
    }

    private function updateAction()
    {
        $post = $this->getRequest()->request;

        $res = Partner::update($post->get('code'), $post->get('projects'));
        $this->tpl->assign('result', $res);

        $map = [
            1 => [ev_gettext('Thank you, the partner was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the partner information.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $info = Partner::getDetails($get->get('code'));
        $this->tpl->assign('info', $info);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            [
                'type' => 'partners',
                'list' => Partner::getList(),
                'project_list' => Project::getAll(),
            ]
        );
    }
}
