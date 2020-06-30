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
use Eventum\Db\DatabaseException;
use Eventum\Db\Doctrine;
use Eventum\Model\Entity;
use Eventum\Model\Repository\EmailAccountRepository;
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
    /** @var EmailAccountRepository */
    private $repo;

    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->repo = Doctrine::getEmailAccountRepository();
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
        $account = $this->updateFromRequest(new Entity\EmailAccount(), $post);

        try {
            $this->repo->updateAccount($account);
        } catch (DatabaseException $e) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to add the new account.'));

            return;
        }

        $this->messages->addInfoMessage(ev_gettext('Thank you, the email account was added successfully.'));
        $this->redirect("email_accounts.php?cat=edit&id={$account->getId()}");
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $account_id = $post->getInt('id');
        $account = $this->updateFromRequest($this->repo->findOrCreate($account_id), $post);

        try {
            $this->repo->updateAccount($account);
        } catch (DatabaseException $e) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to update the account information.'));

            return;
        }

        $this->messages->addInfoMessage(ev_gettext('Thank you, the email account was updated successfully.'));
        $this->redirect("email_accounts.php?cat=edit&id={$account->getId()}");
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;

        try {
            foreach ($post->get('items', []) as $account_id) {
                $account = $this->repo->findById($account_id);
                $this->repo->removeAccount($account);
            }
        } catch (DatabaseException $e) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to delete the account information.'));

            return;
        }

        $this->messages->addInfoMessage(ev_gettext('Thank you, the email account was deleted successfully.'));
        $this->redirect('email_accounts.php');
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
                'list' => $this->getEmailAccounts(),
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
            ->setOnlyNew($post->get('get_only_new') === '1')
            ->setLeaveCopy($post->get('leave_copy') === '1')
            ->setUseRouting($post->get('use_routing') === '1')
            ->setIssueAutoCreationEnabled($post->get('issue_auto_creation') === 'enabled')
            ->setIssueAutoCreationOptions($post->get('options'));

        // password is not updated, if left empty
        if ($post->get('password')) {
            $account->setPassword($post->get('password'));
        }

        // if an account will be used for routing, you can't leave the message on the server
        if ($account->useRouting()) {
            $account->setLeaveCopy(false);
        }

        return $account;
    }

    /**
     * Method used to get the list of available support email
     * accounts in the system.
     *
     * @return  array The list of accounts
     */
    private function getEmailAccounts(): array
    {
        $res = [];
        foreach ($this->repo->findAll() as $account) {
            $row = $account->toArray();
            $row['prj_title'] = Project::getName($row['ema_prj_id']);

            // do not expose as not needed
            unset($row['ema_password']);
            $res[] = $row;
        }

        return $res;
    }
}
