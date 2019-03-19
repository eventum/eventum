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

use Auth;
use Issue;
use Mail_Queue;
use User;

class MailQueueController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'mail_queue.tpl.html';

    /** @var int */
    private $issue_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->query->getInt('iss_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        $role_id = Auth::getCurrentRole();
        if ($role_id < User::ROLE_DEVELOPER) {
            return false;
        }

        $prj_id = Auth::getCurrentProject();
        if (Issue::getProjectID($this->issue_id) != $prj_id) {
            return false;
            // use generic error
            // TODO, cleanup template from 'denied' cause
            //$this->tpl->assign('denied', 1);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $data = Mail_Queue::getListByIssueID($this->issue_id);

        $this->tpl->assign(
            [
                'data' => $data,
                'issue_id' => $this->issue_id,
            ]
        );
    }
}
