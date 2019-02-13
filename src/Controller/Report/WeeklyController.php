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

namespace Eventum\Controller\Report;

use Auth;
use Date_Helper;
use Project;
use Report;
use User;

class WeeklyController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/weekly.tpl.html';

    /** @var string */
    private $week;

    /** @var string */
    private $start_date;

    /** @var string */
    private $end_date;

    /** @var string */
    private $report_type;

    /** @var int */
    private $developer;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->report_type = $request->get('report_type');
        $this->developer = $request->get('developer');
        $this->week = $request->get('week');

        if ($this->hasDate('start')) {
            $this->start_date = implode('-', $request->get('start'));
        } else {
            $this->start_date = $request->query->get('start_date');
        }

        if ($this->hasDate('end')) {
            $this->end_date = implode('-', $request->get('end'));
        } else {
            $this->end_date = $request->query->get('end_date');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    /**
     * @param int $usr_id
     * @return array
     */
    private function getDeveloperReport($usr_id)
    {
        $request = $this->getRequest();

        if ($this->report_type == 'weekly') {
            $dates = explode('_', $this->week);
        } else {
            $dates = [$this->start_date, $this->end_date];
        }

        $options = [
            'separate_closed' => $request->get('separate_closed'),
            'separate_not_assigned_to_user' => $request->get('separate_not_assigned_to_user'),
            'ignore_statuses' => $request->get('ignore_statuses'),
            'show_per_issue' => $request->get('show_per_issue'),
            'separate_no_time' => $request->get('separate_no_time'),
        ];
        $data = Report::getWeeklyReport($usr_id, $this->prj_id, $dates[0], $dates[1], $options);

        // order issues by time spent on them
        if ($request->get('show_per_issue')) {
            $sort_function = function ($a, $b) {
                if ($a['it_spent'] == $b['it_spent']) {
                    return 0;
                }

                return ($a['it_spent'] < $b['it_spent']) ? 1 : -1;
            };
            usort($data['issues']['closed'], $sort_function);
            usort($data['issues']['other'], $sort_function);
            usort($data['issues']['not_mine'], $sort_function);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'weeks' => Date_Helper::getWeekOptions(3, 0),
                'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'report_type' => $this->report_type,
                'week' => $this->week ?: Date_Helper::getCurrentWeek(),
                'developer' => $this->developer ?: Auth::getUserID(),
            ]
        );

        if ($this->developer) {
            $this->tpl->assign('data', $this->getDeveloperReport($this->developer));
        }
    }
}
