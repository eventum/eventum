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

namespace Eventum\Monolog;

use Auth;

/**
 * Class AppVersionProcessor
 * Inject eventum version into logger
 *
 * @package Eventum\Monolog
 */
class AppInfoProcessor
{
    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['extra']['version'] = APP_VERSION;

        $record['extra']['usr_id'] = Auth::getUserID();

        return $record;
    }
}
