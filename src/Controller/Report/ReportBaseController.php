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
use Eventum\Controller\BaseController;

abstract class ReportBaseController extends BaseController
{
    /** @var int */
    protected $usr_id;

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();
        if (!Access::canAccessReports($this->usr_id)) {
            echo 'Invalid role';
            exit;
        }

        return true;
    }
}
