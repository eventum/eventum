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

use Date_Helper;
use Prefs;
use Report;

class WorkloadTimePeriodController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/workload_time_period.tpl.html';

    /** @var string */
    private $type;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->type = $request->query->get('type');
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
        $timezone = Prefs::getTimezone($this->usr_id);

        if ($this->type === 'email') {
            $data = Report::getEmailWorkloadByTimePeriod($timezone);
        } else {
            $data = Report::getWorkloadByTimePeriod($timezone);
        }

        $this->tpl->assign(
            [
                'data' => $data,
                'type' => $this->type,
                'user_tz' => Date_Helper::getTimezoneShortNameByUser($this->usr_id),
            ]
        );
    }
}
