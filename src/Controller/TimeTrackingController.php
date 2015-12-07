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
use Prefs;
use Time_Tracking;
use User;

class TimeTrackingController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'add_time_tracking.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var int */
    private $usr_id;

    /** @var string */
    private $cat;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();

        if (!Issue::canAccess($this->issue_id, $this->usr_id)) {
            return false;
        }

        // FIXME: superfluous check?
        if (Auth::getCurrentRole() <= User::ROLE_CUSTOMER) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'add_time') {
            $res = $this->addTimeEntry();
            $this->tpl->assign('time_add_result', $res);
        }
    }

    private function addTimeEntry()
    {
        $post = $this->getRequest()->request;

        $date = (array) $post->get('date');
        $ttc_id = $post->getInt('category');
        $time_spent = $post->getInt('time_spent');
        $summary = $post->get('summary');
        $res = Time_Tracking::addTimeEntry($this->issue_id, $ttc_id, $time_spent, $date, $summary);

        return $res;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $prj_id = Auth::getCurrentProject();
        $this->tpl->assign(
            array(
                'issue_id' => $this->issue_id,
                'time_categories' => Time_Tracking::getAssocCategories($prj_id),
                'current_user_prefs' => Prefs::get(Auth::getUserID()),
            )
        );
    }
}
