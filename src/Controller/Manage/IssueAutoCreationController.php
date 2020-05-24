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
use CRM;
use Email_Account;
use Eventum\Db\Doctrine;
use Eventum\Model\Entity;
use Priority;
use Project;
use Symfony\Component\HttpFoundation\ParameterBag;
use User;

class IssueAutoCreationController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/issue_auto_creation.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /** @var int */
    private $ema_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->ema_id = $request->request->getInt('ema_id') ?: $request->query->getInt('ema_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $this->prj_id = Email_Account::getProjectID($this->ema_id);

        if ($this->cat === 'update') {
            $this->updateAction();
        }
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $repo = Doctrine::getEmailAccountRepository();
        $account = $this->updateFromRequest($repo->findById($this->ema_id), $post);
        $repo->persistAndFlush($account);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'info' => Email_Account::getDetails($this->ema_id),
                'cats' => Category::getAssocList($this->prj_id),
                'priorities' => Priority::getList($this->prj_id),
                'users' => Project::getUserAssocList($this->prj_id, 'active'),
                'options' => Email_Account::getIssueAutoCreationOptions($this->ema_id),
                'ema_id' => $this->ema_id,
                'prj_title' => Project::getName($this->prj_id),
                'uses_customer_integration' => CRM::hasCustomerIntegration($this->prj_id),
            ]
        );
    }

    private function updateFromRequest(Entity\EmailAccount $account, ParameterBag $post): Entity\EmailAccount
    {
        return $account
            ->setIssueAutoCreationEnabled($post->get('issue_auto_creation') === 'enabled')
            ->setIssueAutoCreationOptions($post->get('options'));
    }
}
