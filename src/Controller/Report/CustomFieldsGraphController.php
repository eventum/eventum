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

class CustomFieldsGraphController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'view.tpl.html';

    /** @var string */
    private $cat;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->get('prj_id') ?: $request->query->get('prj_id');
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

        $plot = new PlotHelper();
        $res = $plot->CustomFieldGraph($type, $custom_field, $custom_options, $group_by, $start_date, $end_date, $interval);
        if (!$res) {
            header('Location: ' . APP_RELATIVE_URL . '/images/no_data.gif');
        }
        exit;
    }
}
