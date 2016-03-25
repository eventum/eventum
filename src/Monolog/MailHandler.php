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

use Misc;
use Monolog;
use Monolog\Handler\NativeMailerHandler;
use Setup;

class MailHandler extends NativeMailerHandler
{
    /**
     * Create mail handler for Eventum errors
     *
     * @param array|int|string $level
     */
    public function __construct($level = Monolog\Logger::ERROR)
    {
        $setup = Setup::get();
        if ($setup['email_error']['status'] == 'enabled') {
            $notify_list = trim($setup['email_error']['addresses']);
            // recipient list can be comma separated
            $to = Misc::trim(explode(',', $notify_list));
        } else {
            $to = null;
        }

        $subject = APP_SITE_NAME . ' - Error found!';

        parent::__construct($to, $subject, $setup['smtp']['from'], $level);
    }
}
