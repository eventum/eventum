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
use Notification;
use User;
use Workflow;

class SelfAssignController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'self_assign.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var string */
    private $target;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('iss_id') ?: $request->query->getInt('iss_id');
        $this->target = $request->get('target');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();
        $this->prj_id = Auth::getCurrentProject();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        // check if issue is assigned to someone else and if so, confirm change.
        if (!$this->target && ($assigned_users = Issue::getAssignedUsers($this->issue_id))) {
            $this->tpl->assign(
                [
                    'prompt_override' => 1,
                    'assigned_users' => $assigned_users,
                ]
            );

            return;
        }

        $issue_details = Issue::getDetails($this->issue_id);

        // force assignment change
        if ($this->target == 'replace') {
            // remove current user(s) first
            Issue::deleteUserAssociations($this->issue_id, $this->usr_id);
        }

        $res = Issue::addUserAssociation($this->usr_id, $this->issue_id, $this->usr_id);
        $this->tpl->assign('self_assign_result', $res);

        $usr_email = User::getEmail($this->usr_id);
        $actions = Notification::getDefaultActions($this->issue_id, $usr_email, 'self_assign');
        Notification::subscribeUser($this->usr_id, $this->issue_id, $this->usr_id, $actions);

        $assigned_usr_ids = Issue::getAssignedUserIDs($this->issue_id);
        Workflow::handleAssignmentChange(
            $this->prj_id,
            $this->issue_id,
            $this->usr_id,
            $issue_details,
            $assigned_usr_ids,
            false
        );

        Notification::notifyAssignmentChange($this->issue_id, $issue_details['assigned_users'], $assigned_usr_ids);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
            ]
        );
    }
}
