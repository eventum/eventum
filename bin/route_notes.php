#!/usr/bin/php
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

ini_set('memory_limit', '1024M');
require_once __DIR__ . '/../init.php';

/**
 * @deprecated this script is deprecated, please use process_all_emails.php script
 */
$script = dirname(__FILE__);
trigger_error("$script is deprecated, use process_all_emails.php instead", E_USER_DEPRECATED);
require __DIR__ . '/process_all_emails.php';
