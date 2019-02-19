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

use LogicException;
use RecentActivity;

/**
 * Class RecentActivityController
 *
 * This report shows a list of activity performed in recent history.
 */
class RecentActivityController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/recent_activity.tpl.html';

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
        try {
            $controller = new RecentActivity();
            $controller($this->tpl);
        } catch (LogicException $e) {
            echo $e->getMessage();
            exit;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
