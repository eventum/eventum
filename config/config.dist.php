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

// This is an template config file for the eventum setup.
// Setup will process this and save as config/config.php.
// You can remove this comment :)

// Contains constants defined for this specific eventum installation.
// This file will not be overwritten when upgrading Eventum

define('APP_NAME', 'Eventum');
define('APP_SHORT_NAME', APP_NAME); // used in the subject of notification emails
define('APP_HOSTNAME', '%{APP_HOSTNAME}%');
define('APP_RELATIVE_URL', '%{APP_RELATIVE_URL}%');
define('APP_BASE_URL', '%{PROTOCOL_TYPE}%' . APP_HOSTNAME . APP_RELATIVE_URL);
define('APP_COOKIE_URL', APP_RELATIVE_URL);
define('APP_COOKIE_DOMAIN', null);
