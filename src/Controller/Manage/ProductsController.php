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
use Product;
use Project;

class ProductsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/products.tpl.html';

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
        $post = $this->getRequest()->request;

        $res = Product::insert(
            $post->get('title'),
            $post->get('version_howto'),
            $post->get('rank'),
            $post->get('removed'),
            $post->get('email')
        );
        $map = [
            1 => ['Thank you, the product was added successfully.', MessagesHelper::MSG_INFO],
            -1 => ['An error occurred while trying to add the product.', MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Product::update(
            $post->get('id'),
            $post->get('title'),
            $post->get('version_howto'),
            $post->get('rank'),
            $post->get('removed'),
            $post->get('email')
        );
        $map = [
            1 => ['Thank you, the product was updated successfully.', MessagesHelper::MSG_INFO],
            -1 => ['An error occurred while trying to update the product.', MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;

        Product::remove($post->get('items'));
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $info = Product::getDetails($get->getInt('id'));
        $this->tpl->assign('info', $info);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => Product::getList(),
                'project_list' => Project::getAll(),
            ]
        );
    }
}
