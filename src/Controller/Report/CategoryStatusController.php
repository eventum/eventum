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
use Report;
use Status;

class CategoryStatusController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/category_statuses.tpl.html';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
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
        $categories = Category::getAssocList($this->prj_id);
        $statuses = Status::getAssocStatusList($this->prj_id, true);
        $data = Report::getCategoryStatusReport($this->prj_id, $categories, $statuses);

        $this->tpl->assign(
            [
                'statuses' => $statuses,
                'categories' => $categories,
                'data' => $data,
            ]
        );
    }
}
