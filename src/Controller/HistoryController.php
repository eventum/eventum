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
use History;
use Reminder;
use User;

class HistoryController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'history.tpl.html';

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
        Auth::checkAuthentication(null, true);

        $usr_id = Auth::getUserID();
        if (!Access::canViewHistory($this->issue_id, $usr_id)) {
            return false;
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
        $this->tpl->assign([
            'issue_id' => $this->issue_id,
            'changes' => History::getListing($this->issue_id),
        ]);

        $role_id = Auth::getCurrentRole();
        if ($role_id > User::ROLE_CUSTOMER) {
            $this->tpl->assign('reminders', Reminder::getHistoryList($this->issue_id));
        }
    }
}
