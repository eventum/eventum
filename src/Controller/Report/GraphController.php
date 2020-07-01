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

use Setup;

/**
 * Class GraphController
 */
class GraphController extends ReportBaseController
{
    /** @var string */
    private $graph;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->graph = $request->request->get('graph') ?: $request->query->get('graph');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->graph) {
            case 'custom_fields':
                $res = $this->graphCustomFields();
                break;
            case 'workload_date_range':
                $res = $this->graphWorkloadDateRange();
                break;
            case 'workload_time_period':
                $res = $this->graphWorkloadTimePeriod();
                break;
            default:
                $res = false;
        }

        if (!$res) {
            header('Location: ' . Setup::getRelativeUrl() . '/images/no_data.gif');
        }
        exit;
    }

    private function graphCustomFields()
    {
        $request = $this->getRequest();
        $get = $request->query;

        if ($this->hasDate('start')) {
            $start_date = implode('-', $request->get('start'));
        } else {
            $start_date = null;
        }

        if ($this->hasDate('end')) {
            $end_date = implode('-', $request->get('end'));
        } else {
            $end_date = null;
        }

        $custom_field = $get->get('custom_field');
        $custom_options = $get->get('custom_options');
        $group_by = $get->get('group_by');
        $interval = $request->get('interval');
        $type = $get->get('type');

        return $this->plot->CustomFieldGraph(
            $type,
            $custom_field,
            $custom_options,
            $group_by,
            $start_date,
            $end_date,
            $interval
        );
    }

    private function graphWorkloadDateRange()
    {
        $request = $this->getRequest();

        $interval = $request->get('interval');
        $graph = $request->get('subgraph');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $type = $request->get('type');

        return $this->plot->WorkloadDateRangeGraph($graph, $type, $start_date, $end_date, $interval);
    }

    private function graphWorkloadTimePeriod()
    {
        $get = $this->getRequest()->query;

        $this->plot->WorkloadTimePeriodGraph($get->get('type'));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
