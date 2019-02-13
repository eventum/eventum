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

use Report;

class OpenIssuesController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/open_issues.tpl.html';

    /** @var int */
    private $cutoff_days;

    /** @var bool */
    private $group_by_reporter;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();
        $post = $request->request;
        $get = $request->query;

        $this->cutoff_days = $get->getInt('cutoff_days', 7);
        $this->group_by_reporter = $post->getBoolean('group_by_reporter') ?: $get->getBoolean('group_by_reporter');
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
        $res = Report::getOpenIssuesByUser($this->prj_id, $this->cutoff_days, $this->group_by_reporter);
        $this->tpl->assign(
            [
                'cutoff_days' => $this->cutoff_days,
                'group_by_reporter' => $this->group_by_reporter,
                'users' => $res,
            ]
        );
    }
}
