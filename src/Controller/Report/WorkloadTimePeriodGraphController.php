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

class WorkloadTimePeriodGraphController extends ReportBaseController
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
        $get = $this->getRequest()->query;

        $plot = new PlotHelper();
        $plot->WorkloadTimePeriodGraph($get->get('type'));
        exit;
    }
}
