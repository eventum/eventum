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

use Category;
use Date_Helper;
use Eventum\Session;
use Report;

class WorkloadDateRangeController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/workload_date_range.tpl.html';

    /** @var string */
    private $start_date;

    /** @var string */
    private $end_date;

    /** @var string */
    private $type;

    /** @var string */
    private $interval;

    /** @var string */
    private $category;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->interval = $request->get('interval');
        $this->type = $request->get('type');
        $this->category = $request->get('category');

        if ($this->hasDate('start')) {
            $this->start_date = implode('-', $request->get('start'));
        }

        if ($this->hasDate('end')) {
            $this->end_date = implode('-', $request->get('end'));
        }
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
        $types = [
            'individual' => ev_gettext('Individual'),
            'aggregate' => ev_gettext('Aggregate'),
        ];

        // if empty start date, set to be a month ago
        $start_date = $this->start_date ?: date('Y-m-d', time() - Date_Helper::MONTH);
        $end_date = $this->end_date ?: date('Y-m-d');

        if ($this->interval) {
            $data = Report::getWorkloadByDateRange(
                $this->interval,
                $this->type,
                $start_date,
                date('Y-m-d', strtotime($end_date) + Date_Helper::DAY),
                $this->category
            );
            Session::set('workload_date_range_data', $data);
            $this->tpl->assign('data', $data);
        }

        $this->tpl->assign(
            [
                'interval' => $this->interval,
                'types' => $types,
                'type' => $this->type,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'categories' => Category::getAssocList($this->prj_id),
                'category' => $this->category,
            ]
        );
    }
}
