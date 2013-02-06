<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

// Contains constants defined for this specific eventum installation.
// This file will not be overwritten when upgrading Eventum

// definitions of SQL variables
define('APP_SQL_DBTYPE', 'mysql');
define('APP_SQL_DBHOST', '%{APP_SQL_DBHOST}%');
define('APP_SQL_DBPORT', 3306);
define('APP_SQL_DBNAME', '%{APP_SQL_DBNAME}%');
define('APP_SQL_DBUSER', '%{APP_SQL_DBUSER}%');
define('APP_SQL_DBPASS', '%{APP_SQL_DBPASS}%');

define('APP_DEFAULT_DB', APP_SQL_DBNAME);
define('APP_TABLE_PREFIX', '%{APP_TABLE_PREFIX}%');

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
define('APP_PROJECT_COOKIE_EXPIRE', time() + (60 * 60 * 24));

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
define('APP_CELL_COLOR', '#255282');
define('APP_LIGHT_COLOR', '#DDDDDD');
define('APP_MIDDLE_COLOR', '#CACACA');
define('APP_DARK_COLOR', '#CACACA');
define('APP_CYCLE_COLORS', '#DDDDDD,#CACACA');
define('APP_INTERNAL_COLOR', '#9C494B');

// define the user_id of system user
define('APP_SYSTEM_USER_ID', 1);

// define the type of password hashing to use (MD5, MD5-64)
define('APP_HASH_TYPE', 'MD5');

// if full text searching is enabled
define('APP_ENABLE_FULLTEXT', '%{APP_ENABLE_FULLTEXT}%');
define('APP_FULLTEXT_SEARCH_CLASS', 'mysql_fulltext_search');

// auth backend. 'mysql_auth_backend' (default), 'ldap_auth_backend' for ldap
//define('APP_AUTH_BACKEND', 'mysql_auth_backend');

// 'native' or 'php'. Try native first, if you experience strange issues
// such as language switching randomly, try php
define('APP_GETTEXT_MODE', 'native');

// director where to save routed drafts/notes/emails. leave empty/undefined to disable.
define('APP_ROUTED_MAILS_SAVEDIR', APP_PATH . '/misc');
