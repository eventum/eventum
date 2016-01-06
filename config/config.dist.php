<?php
// File: config.dist.php
// This is an template config file for the eventum setup.
// Setup will process this and save as config/config.php.
// You can remove this comment :)

// Contains constants defined for this specific eventum installation.
// This file will not be overwritten when upgrading Eventum

// NOTE: if you are looking for database connection info in this file,
// then since 2.4.0 database configuration is in setup.php file.

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

define('APP_DEFAULT_PAGER_SIZE', 5);
define('APP_DEFAULT_REFRESH_RATE', 5); // in minutes

// new users will use these for default preferences
// if the user will recieve an email when an issue is assigned to him
define('APP_DEFAULT_ASSIGNED_EMAILS', true);
// if the user will recieve an email when ANY issue is created
define('APP_DEFAULT_NEW_EMAILS', false);
// locale used for localized messages
define('APP_DEFAULT_LOCALE', 'en_US');
// timezone for displayed times in web and emails
define('APP_DEFAULT_TIMEZONE', '%{APP_DEFAULT_TIMEZONE}%');
// default day of week start: 0 = sunday; 1 = monday
define('APP_DEFAULT_WEEKDAY', '%{APP_DEFAULT_WEEKDAY}%');

// application charset, there is no good reason to use anything else than utf8,
// unless you use really old mysql which doesn't support charsets
define('APP_CHARSET', '%{CHARSET}%');

// define colors used by eventum
define('APP_INTERNAL_COLOR', '#9C494B');

// define the user_id of system user
define('APP_SYSTEM_USER_ID', 1);

// if full text searching is enabled
define('APP_ENABLE_FULLTEXT', '%{APP_ENABLE_FULLTEXT}%');
define('APP_FULLTEXT_SEARCH_CLASS', 'mysql_fulltext_search');

// auth backend. 'Mysql_Auth_Backend' (default), 'LDAP_Auth_Backend' for LDAP
//define('APP_AUTH_BACKEND', 'LDAP_Auth_Backend');

// 'native' or 'php'. Try native first, if you experience strange issues
// such as language switching randomly, try php
define('APP_GETTEXT_MODE', 'native');

// directory where to save routed drafts/notes/emails. use NULL or '' to disable.
define('APP_ROUTED_MAILS_SAVEDIR', APP_PATH . '/misc');
