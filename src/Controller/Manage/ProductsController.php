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
        $post = $this->getRequest()->request;

        $res = Product::insert(
            $post->get('title'), $post->get('version_howto'), $post->get('rank'),
            $post->get('removed'), $post->get('email')
        );
        $map = array(
            1 => array('Thank you, the product was added successfully.', Misc::MSG_INFO),
            -1 => array('An error occurred while trying to add the product.', Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $post = $this->getRequest()->request;

        $res = Product::update(
            $post->get('id'), $post->get('title'), $post->get('version_howto'),
            $post->get('rank'), $post->get('removed'), $post->get('email')
        );
        $map = array(
            1 => array('Thank you, the product was updated successfully.', Misc::MSG_INFO),
            -1 => array('An error occurred while trying to update the product.', Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        $post = $this->getRequest()->request;

        Product::remove($post->get('items'));
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $info = Product::getDetails($get->getInt('id'));
        $this->tpl->assign('info', $info);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'list' => Product::getList(),
                'project_list' => Project::getAll(),
            )
        );
    }
}
