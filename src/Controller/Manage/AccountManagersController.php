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

use CRM;
use Eventum\Controller\Helper\MessagesHelper;
use Project;
use User;

class AccountManagersController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/account_managers.tpl.html';

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
        $this->prj_id = $request->query->getInt('prj_id') ?: $request->request->getInt('prj_id');
        $this->id = $request->query->getInt('id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat == 'new') {
            $res = CRM::insertAccountManager();
            $this->mapMessages($res);
        } elseif ($this->cat == 'update') {
            $res = CRM::updateAccountManager();
            $this->mapMessages($res);
        } elseif ($this->cat == 'delete') {
            CRM::removeAccountManager();
        } elseif ($this->prj_id) {
            $crm = CRM::getInstance($this->prj_id);
            $this->tpl->assign(
                [
                    'info' => ['cam_prj_id' => $this->prj_id],
                    'customers' => $crm->getCustomerAssocList(),
                ]
            );
        }

        if ($this->cat == 'edit') {
            $info = CRM::getAccountManagerDetails($this->id);
            if ($this->prj_id) {
                $info['cam_prj_id'] = $this->prj_id;
            }
            $this->tpl->assign(
                [
                    'customers' => CRM::getInstance($info['cam_prj_id'])->getCustomerAssocList(),
                    'user_options' => User::getActiveAssocList($info['cam_prj_id'], User::ROLE_CUSTOMER),
                    'info' => $info,
                ]
            );
        }
    }

    /**
     * @param int $res
     */
    private function mapMessages($res): void
    {
        $map = [
            1 => [ev_gettext('Thank you, the account manager was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the account manager.'), MessagesHelper::MSG_ERROR],
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
                'project_list' => Project::getAll(false),
                'list' => CRM::getAccountManagerList(),
            ]
        );

        if ($this->prj_id) {
            $user_options = User::getActiveAssocList($this->prj_id, User::ROLE_CUSTOMER);
            $this->tpl->assign('user_options', $user_options);
        }
    }
}
