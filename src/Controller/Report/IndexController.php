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

use Access;
use Auth;

class IndexController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/index.tpl.html';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();
        if (!Access::canAccessReports(Auth::getUserID())) {
            $this->redirect(APP_RELATIVE_URL . 'main.php');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
