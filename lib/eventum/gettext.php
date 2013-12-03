<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2013 Eventum Team.                                     |
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
// | Authors:                                                             |
// |  Bryan Alsdorf <bryan@mysql.com>                                     |
// |  Elan Ruusamäe <glen@delfi.ee>                                       |
// +----------------------------------------------------------------------+
//

// if there is no gettext support built into PHP, or we are running in language compatability mode include PHP-gettext
if (!function_exists('gettext') || (defined('APP_GETTEXT_MODE') && APP_GETTEXT_MODE == 'php')) {
    require_once APP_PHP_GETTEXT_PATH. '/gettext.inc';

    function ev_gettext($string) {
        if (func_num_args() > 1) {
            $arg = array();
            for($i = 1 ; $i < func_num_args(); $i++) {
                $arg[] = func_get_arg($i);
            }
            $string = _gettext($string);
            return vsprintf($string, $arg);
        } else {
            return _gettext($string);
        }
    }
    function ev_ngettext($string, $plural, $number) {
        return _ngettext($string, $plural, $number);
    }

} else {
    function ev_gettext($string) {
        if (func_num_args() > 1) {
            $arg = array();
            for($i = 1 ; $i < func_num_args(); $i++) {
                $arg[] = func_get_arg($i);
            }

            $string = gettext($string);
            return vsprintf($string, $arg);
        } else {
            return gettext($string);
        }
    }

    function ev_ngettext($string, $plural, $number) {
        return ngettext($string, $plural, $number);
    }
    function _bind_textdomain_codeset($domain, $codeset) {
        return bind_textdomain_codeset($domain, $codeset);
    }
    function _bindtextdomain($domain, $path) {
        return bindtextdomain($domain, $path);
    }
    function _textdomain($domain) {
        return textdomain($domain);
    }
    function _gettext($msgid) {
        return gettext($msgid);
    }
    function _setlocale($category, $locale) {
        return setlocale($category, $locale);
    }
}

// this won't change over the request. so set it once and permanently
_bindtextdomain('eventum', APP_PATH . '/localization/');
_bind_textdomain_codeset('eventum', APP_CHARSET);
_textdomain('eventum');
