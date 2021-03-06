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

use Eventum\Kernel;

require_once __DIR__ . '/../../autoload.php';

header('Content-Type: text/html; charset=UTF-8');

ini_set('memory_limit', '64M');
error_reporting(E_ALL & ~E_STRICT);
set_time_limit(0);

Kernel::handleRequest();
