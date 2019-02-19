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

namespace Eventum\Controller\Helper;

use Eventum\Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class LoggerHelper implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = [])
    {
        Logger::app()->log($level, $message, $context);
    }
}
