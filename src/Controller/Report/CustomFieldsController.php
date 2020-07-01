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
use Project;
use Report;
use User;

class CustomFieldsController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/custom_fields.tpl.html';

    /** @var int */
    private $custom_field;

    /** @var int[] */
    private $custom_options;

    /** @var string */
    private $group_by;

    /** @var string */
    private $interval;

    /** @var int */
    private $assignee;

    /** @var string */
    private $start;

    /** @var string */
    private $end;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->custom_field = $request->query->getInt('custom_field');
        $this->custom_options = $request->query->get('custom_options');
        $this->group_by = $request->query->get('group_by');

        $this->interval = $request->get('interval');
        $this->assignee = $request->get('assignee');

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

    private function getCustomFields()
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
            // FIXME: ugly & wrong
            echo ev_gettext('No custom fields for this project');
            exit;
        }

        $this->tpl->assign('options', $options);

        return $custom_fields;
    }

    private function getReport()
    {
        if (!$this->custom_field) {
            return null;
        }

        $this->tpl->assign('field_info', Custom_Field::getDetails($this->custom_field));

        return Report::getCustomFieldReport(
            $this->custom_field,
            $this->custom_options,
            $this->group_by,
            $this->start,
            $this->end,
            true,
            $this->interval,
            $this->assignee
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'custom_fields' => $this->getCustomFields(),
                'custom_field' => $this->custom_field,
                'custom_options' => $this->custom_options,
                'group_by' => $this->group_by,
                'selected_options' => $this->custom_options,
                'data' => $this->getReport(),
                'start_date' => $this->start ?: '--',
                'end_date' => $this->end ?: '--',
                'assignees' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'assignee' => $this->assignee,
            ]
        );
    }
}
