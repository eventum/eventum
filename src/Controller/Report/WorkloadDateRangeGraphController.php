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

use PlotHelper;

class WorkloadDateRangeGraphController extends ReportBaseController
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $request = $this->getRequest();

        $interval = $request->get('interval');
        $graph = $request->get('graph');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $type = $request->get('type');

        $plot = new PlotHelper();
        $res = $plot->WorkloadDateRangeGraph($graph, $type, $start_date, $end_date, $interval);
        if (!$res) {
            header('Location: ../images/no_data.gif');
            exit;
        }
    }
}
