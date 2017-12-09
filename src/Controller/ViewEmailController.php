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

namespace Eventum\Controller;

use Access;
use Auth;
use Email_Account;
use Issue;
use Mail_Queue;
use Project;
use Support;
use User;

class ViewEmailController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'view_email.tpl.html';

    /** @var int */
    private $sup_id;

    /** @var int */
    private $ema_id;

    /** @var int */
    private $new_ema_id;

    /** @var int */
    private $issue_id;

    /** @var string */
    private $cat;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->sup_id = $request->query->getInt('id');
        $this->ema_id = $request->query->getInt('ema_id');
        $this->new_ema_id = $request->query->getInt('new_ema_id');
        $this->cat = $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        if (!$this->sup_id) {
            return false;
        }

        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();
        $this->prj_id = Auth::getCurrentProject();

        $this->issue_id = Support::getIssueFromEmail($this->sup_id);
        if (!$this->issue_id) {
            return Access::canAccessAssociateEmails($this->usr_id);
        }

        if (!Issue::canAccess($this->issue_id, $this->usr_id)) {
            return false;
        }

        $usr_role = User::getRoleByUser($this->usr_id, $this->prj_id);
        if ($this->cat == 'move_email' && $usr_role < User::ROLE_USER) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'list_emails') {
            $this->listEmailsAction();
        } elseif ($this->cat == 'move_email') {
            $this->moveMailAction();
        } else {
            $sides = Support::getIssueSides($this->issue_id, $this->sup_id);
            $this->tpl->assign(
                [
                    'previous' => $sides['previous'],
                    'next' => $sides['next'],
                ]
            );
        }
    }

    private function listEmailsAction()
    {
        $sides = Support::getListingSides($this->sup_id);
        $this->tpl->assign(
            [
                'previous' => $sides['previous'],
                'next' => $sides['next'],
            ]
        );
    }

    private function moveMailAction()
    {
        $res = Support::moveEmail($this->sup_id, $this->ema_id, $this->new_ema_id);
        $this->tpl->assign(
            [
                'move_email_result' => $res,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $email = Support::getEmailDetails($this->sup_id);
        $email['seb_body'] = str_replace('&amp;nbsp;', '&nbsp;', $email['seb_body']);

        $recipients = Mail_Queue::getMessageRecipients(['customer_email', 'other_email'], $this->sup_id);
        $projects = Project::getAssocList($this->usr_id);
        $email_accounts = Email_Account::getAssocList(array_keys($projects), true);
        $seq_id = Support::getSequenceByID($this->sup_id);

        // TRANSLATORS: $1 - issue_id, $2 - email subject, $3 - email_id
        $extra_title = ev_gettext('Issue #%1$s Email #%3$s: %2$s', $this->issue_id, $email['sup_subject'], $seq_id);

        $this->tpl->assign(
            [
                'email' => $email,
                'issue_id' => $this->issue_id,
                'extra_title' => $extra_title,
                'email_accounts' => $email_accounts,
                'recipients' => $recipients,
            ]
        );
    }
}
