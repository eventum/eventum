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
define('APP_SITE_NAME', APP_NAME);
define('APP_RELATIVE_URL', '%{APP_RELATIVE_URL}%');
define('APP_BASE_URL', '%{PROTOCOL_TYPE}%' . APP_HOSTNAME . APP_RELATIVE_URL);
define('APP_COOKIE_URL', APP_RELATIVE_URL);
define('APP_COOKIE_DOMAIN', null);
define('APP_COOKIE', 'eventum');
define('APP_COOKIE_EXPIRE', time() + (60 * 60 * 8));
define('APP_PROJECT_COOKIE', 'eventum_project');
define('APP_PROJECT_COOKIE_EXPIRE', time() + (60 * 60 * 24 * 30)); // 30 days

// locale used for localized messages
define('APP_DEFAULT_LOCALE', 'en_US');

// define colors used by eventum
define('APP_INTERNAL_COLOR', '#9C494B');

// define the user_id of system user
define('APP_SYSTEM_USER_ID', 1);

// if full text searching is enabled
define('APP_ENABLE_FULLTEXT', '%{APP_ENABLE_FULLTEXT}%');
define('APP_FULLTEXT_SEARCH_CLASS', 'mysql_fulltext_search');

// 'native' or 'php'. Try native first, if you experience strange issues
// such as language switching randomly, try php
define('APP_GETTEXT_MODE', 'native');

// directory where to save routed drafts/notes/emails. use NULL or '' to disable.
define('APP_ROUTED_MAILS_SAVEDIR', APP_PATH . '/misc');
