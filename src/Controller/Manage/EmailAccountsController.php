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
use Eventum\Controller\Helper\MessagesHelper;
use Eventum\Crypto\CryptoManager;
use Eventum\Db\DatabaseException;
use Eventum\Db\Doctrine;
use Eventum\Model\Entity;
use Project;
use Symfony\Component\HttpFoundation\ParameterBag;
use User;

class EmailAccountsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/email_accounts.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    protected function defaultAction(): void
    {
        if ($this->cat === 'new') {
            $this->newAction();
        } elseif ($this->cat === 'update') {
            $this->updateAction();
        } elseif ($this->cat === 'delete') {
            $this->deleteAction();
        } elseif ($this->cat === 'edit') {
            $this->editAction();
        }
    }

    private function newAction(): void
    {
        $post = $this->getRequest()->request;

        $repo = Doctrine::getEmailAccountRepository();
        $account = $this->updateFromRequest(new Entity\EmailAccount(), $post);

        try {
            $repo->updateAccount($account);
        } catch (DatabaseException $e) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to add the new account.'));

            return;
        }

        $this->messages->addInfoMessage(ev_gettext('Thank you, the email account was added successfully.'));
        $this->redirect("email_accounts.php?cat=edit&id={$account->getId()}");
    }

    private function updateAction(): void
    {
        $map = [
            1 => [ev_gettext('Thank you, the email account was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the account information.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages(Email_Account::update(), $map);
    }

    private function deleteAction(): void
    {
        $map = [
            1 => [ev_gettext('Thank you, the email account was deleted successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to delete the account information.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages(Email_Account::remove(), $map);
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $this->tpl->assign('info', Email_Account::getDetails($get->get('id')));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'list' => Email_Account::getList(),
                'all_projects' => Project::getAll(),
            ]
        );
    }

    private function updateFromRequest(Entity\EmailAccount $account, ParameterBag $post): Entity\EmailAccount
    {
        $account
            ->setProjectId($post->getInt('project'))
            ->setType($post->get('type'))
            ->setHostName($post->get('hostname'))
            ->setPort($post->getInt('port'))
            ->setFolder($post->get('folder'))
            ->setUserName($post->get('username'))
            ->setPassword(CryptoManager::encrypt($post->get('password')))
            ->setOnlyNew($post->get('get_only_new') === '1')
            ->setLeaveCopy($post->get('leave_copy') === '1')
            ->setUseRouting($post->get('use_routing') === '1')
            ->setIssueAutoCreationEnabled($post->get('issue_auto_creation') === 'enabled')
            ->setIssueAutoCreationOptions($post->get('options'));

        // if an account will be used for routing, you can't leave the message on the server
        if ($account->useRouting()) {
            $account->setLeaveCopy(false);
        }

        return $account;
    }
}
