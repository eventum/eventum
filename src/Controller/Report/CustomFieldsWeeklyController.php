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

use Custom_Field;
use Date_Helper;
use Report;

class CustomFieldsWeeklyController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/custom_fields_weekly.tpl.html';

    /** @var string */
    private $week;

    /** @var array */
    private $custom_field;

    /** @var string */
    private $report_type;

    /** @var string */
    private $start;

    /** @var string */
    private $end;

    /** @var bool */
    private $per_user;

    /** @var array */
    private $custom_options;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->report_type = $request->get('report_type');
        $this->week = $request->get('week');
        $this->custom_field = $request->get('custom_field');
        $this->custom_options = $request->get('custom_options');
        $this->per_user = (bool)$request->get('time_per_user');

        if ($this->hasDate('start')) {
            $this->start = implode('-', $request->get('start'));
        }

        if ($this->hasDate('end')) {
            $this->end = implode('-', $request->get('end'));
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
        // get list of fields and convert info useful arrays
        $fields = Custom_Field::getListByProject($this->prj_id, '');
        $custom_fields = [];
        $options = [];
        if (is_array($fields) && count($fields) > 0) {
            foreach ($fields as $field) {
                $custom_fields[$field['fld_id']] = $field['fld_title'];
                $options[$field['fld_id']] = Custom_Field::getOptions($field['fld_id']);
            }
        } else {
            echo ev_gettext('No custom fields for this project');
            exit;
        }

        $this->tpl->assign(
            [
                'custom_fields' => $custom_fields,
                'custom_field' => $this->custom_field,
                'options' => $options,
                'custom_options' => $this->custom_options,
                'selected_options' => $this->custom_options,
                'start_date' => $this->start,
                'end_date' => $this->end,
                'report_type' => $this->report_type,
                'per_user' => $this->per_user,
                'weeks' => Date_Helper::getWeekOptions(3, 0),
                'week' => $this->week ?: Date_Helper::getCurrentWeek(),
            ]
        );

        if ($this->custom_field) {
            $this->tpl->assign(
                [
                    'field_info' => Custom_Field::getDetails($this->custom_field),
                ]
            );
        }

        if (count($this->custom_field) > 0) {
            // split date up
            if ($this->report_type == 'weekly') {
                $dates = explode('_', $this->week);
            } else {
                $dates = [$this->start, $this->end];
            }

            $data = Report::getCustomFieldWeeklyReport(
                $this->custom_field,
                $this->custom_options,
                $dates[0],
                $dates[1],
                $this->per_user
            );
            $this->tpl->assign(
                [
                    'data' => $data,
                ]
            );
        }
    }
}
