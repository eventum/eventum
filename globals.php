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

/*
 * Constants here are internal and can not be overriden by installation
 */

use Eventum\Config\Paths;

/**
 * @deprecated constants to be dropped in 3.9.0
 */
// "APP_LOG_PATH" - may be present in config/logger.php
define('APP_LOG_PATH', Paths::APP_LOG_PATH);
