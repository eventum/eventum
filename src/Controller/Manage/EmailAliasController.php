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
use User;

class EmailAliasController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/email_alias.tpl.html';

    /** @var bool */
    protected $is_popup = true;

    /** @var string */
    private $cat;

    /** @var int */
    private $usr_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->usr_id = $request->request->getInt('id') ?: $request->query->getInt('id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat == 'save') {
            $this->saveAction();
        } elseif ($this->cat == 'remove') {
            $this->removeAction();
        }
    }

    private function saveAction(): void
    {
        $post = $this->getRequest()->request;

        $res = User::addAlias($this->usr_id, trim($post->get('alias')));
        $map = [
            true => [ev_gettext('Thank you, the alias was added successfully.'), MessagesHelper::MSG_INFO],
            false => [ev_gettext('An error occurred while trying to add the alias.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function removeAction(): void
    {
        $post = $this->getRequest()->request;

        $map = [
            true => [ev_gettext('Thank you, the alias was removed successfully.'), MessagesHelper::MSG_INFO],
            false => [ev_gettext('An error occurred while trying to remove the alias.'), MessagesHelper::MSG_ERROR],
        ];

        foreach ($post->get('item') as $alias) {
            $res = User::removeAlias($this->usr_id, $alias);
            $this->messages->mapMessages($res, $map);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => User::getAliases($this->usr_id),
                'username' => User::getFullName($this->usr_id),
                'id' => $this->usr_id,
            ]
        );
    }
}
