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
use Date_Helper;
use DateInterval;
use Eventum\Db\DatabaseException;
use Issue;
use Time_Tracking;
use User;

class TimeTrackingController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'time_tracking_entry.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var int */
    private $ttr_id;

    /** @var array */
    private $time_tracking_details;

    /** @var int */
    private $usr_id;

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = $request->request->get('cat');
        $this->ttr_id = $request->request->getInt('ttr_id') ?: $request->query->getInt('ttr_id');

        if ($this->ttr_id) {
            $this->time_tracking_details = Time_Tracking::getTimeEntryDetails($this->ttr_id);
            $this->issue_id = $this->time_tracking_details['ttr_iss_id'];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();

        if ($this->ttr_id) {
            if (!($this->time_tracking_details['ttr_usr_id'] == $this->usr_id or Auth::getCurrentRole() >= User::ROLE_MANAGER)) {
                return false;
            }
        }

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
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat == 'add_time') {
            $res = $this->addTimeEntry();
            $this->tpl->assign('time_add_result', $res);
        } elseif ($this->cat == 'update_time') {
            $res = $this->updateTimeEntry();
            $this->tpl->assign('time_update_result', $res);
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

    private function updateTimeEntry()
    {
        $post = $this->getRequest()->request;

        $date = (array) $post->get('date');
        $ttc_id = $post->getInt('category');
        $time_spent = $post->getInt('time_spent');
        $summary = $post->get('summary');
        try {
            $res = Time_Tracking::updateTimeEntry($this->ttr_id, $ttc_id, $time_spent, $date, $summary);
        } catch (DatabaseException $e) {
            $res = -1;
        }

        return $res;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $prj_id = Auth::getCurrentProject();
        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'time_categories' => Time_Tracking::getAssocCategories($prj_id),
            ]
        );

        if ($this->time_tracking_details) {
            $this->tpl->assign([
                'details' => $this->time_tracking_details,
                'start_date' => Date_Helper::getDateTime($this->time_tracking_details['ttr_created_date']),
                'end_date' => Date_Helper::getDateTime($this->time_tracking_details['ttr_created_date'])->sub(
                    new DateInterval('PT' . $this->time_tracking_details['ttr_time_spent'] . 'M')
                ),
            ]);
        }
    }
}
