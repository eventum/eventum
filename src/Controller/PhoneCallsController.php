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
use Phone_Support;
use User;

class PhoneCallsController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'add_phone_entry.tpl.html';

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
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        if (Auth::getCurrentRole() <= User::ROLE_CUSTOMER) {
            return false;
        }

        $this->usr_id = Auth::getUserID();
        if (!Issue::canAccess($this->issue_id, $this->usr_id)) {
            return false;
        }

        $this->prj_id = Issue::getProjectID($this->issue_id);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat === 'add_phone') {
            $res = Phone_Support::insert();
            $this->tpl->assign('add_phone_result', $res);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'phone_categories' => Phone_Support::getCategoryAssocList($this->prj_id),
            ]
        );
    }
}
