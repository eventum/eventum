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
use Group;
use Project;
use Report;
use Status;
use User;

class StalledIssuesController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/stalled_issues.tpl.html';

    /** @var string */
    private $cat;

    /** @var string */
    private $before;

    /** @var string */
    private $after;

    /** @var string */
    private $sort_order;

    /** @var string[] */
    private $status;

    /** @var string[] */
    private $developers;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->get('prj_id') ?: $request->query->get('prj_id');

        $this->sort_order = $request->get('sort_order') ?: 'ASC';
        $this->status = $request->get('status');
        $this->developers = $request->get('developers');

        if ($this->hasDate('before')) {
            $this->before = implode('-', $request->get('before'));
        }
        if ($this->hasDate('after')) {
            $this->after = implode('-', $request->get('after'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    private function getAssignOptions()
    {
        $groups = Group::getAssocList($this->prj_id);
        $assign_options = [];
        if (count($groups) > 0 && Auth::getCurrentRole() > User::ROLE_CUSTOMER) {
            foreach ($groups as $grp_id => $grp_name) {
                $assign_options["grp:$grp_id"] = 'Group: ' . $grp_name;
            }
        }
        $assign_options += Project::getUserAssocList($this->prj_id, 'active', User::ROLE_USER);

        return $assign_options;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $now = time();
        $before = $this->before ?: date('Y-m-d', $now - Date_Helper::MONTH);
        $after = $this->after ?: date('Y-m-d', $now - Date_Helper::YEAR);

        $data = Report::getStalledIssuesByUser(
            $this->prj_id,
            $this->developers,
            $this->status,
            $before,
            $after,
            $this->sort_order
        );

        $this->tpl->assign(
            [
                'users' => $this->getAssignOptions(),
                'before_date' => $before,
                'after_date' => $after,
                'data' => $data,
                'developers' => $this->developers,
                'status_list' => Status::getAssocStatusList($this->prj_id),
                'status' => $this->status,
                'sort_order' => $this->sort_order,
            ]
        );
    }
}
