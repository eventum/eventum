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

use Email_Account;
use Misc;
use Project;
use User;

class EmailAccountsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/email_accounts.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

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
        $map = array(
            1 => array(ev_gettext('Thank you, the email account was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new account.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages(Email_Account::insert(), $map);
    }

    private function updateAction()
    {
        $map = array(
            1 => array(ev_gettext('Thank you, the email account was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the account information.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages(Email_Account::update(), $map);
    }

    private function deleteAction()
    {
        $map = array(
            1 => array(ev_gettext('Thank you, the email account was deleted successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to delete the account information.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages(Email_Account::remove(), $map);
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Email_Account::getDetails($get->get('id')));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'list' => Email_Account::getList(),
                'all_projects' => Project::getAll(),
            )
        );
    }
}
