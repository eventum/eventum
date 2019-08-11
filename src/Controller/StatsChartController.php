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

namespace Eventum\Controller;

use Auth;
use Eventum\Config\Paths;

/**
 * Class StatsChartController
 */
class StatsChartController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $request = $this->getRequest();

        $type = $request->query->get('plot');
        $hide_closed = $request->get('hide_closed');

        $res = $this->plot->StatsChart($type, $hide_closed);
        if (!$res) {
            header('Content-type: image/gif');
            readfile(Paths::APP_PATH . '/htdocs/images/no_data.gif');
        }
        exit;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
