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

// if there is no gettext support built into PHP, or we are running in language compatability mode include PHP-gettext
if (!function_exists('gettext') || (defined('APP_GETTEXT_MODE') && APP_GETTEXT_MODE == 'php')) {
    /** @noinspection PhpIncludeInspection */
    require_once APP_PHP_GETTEXT_PATH. '/gettext.inc';

    function ev_gettext($string)
    {
        if (func_num_args() > 1) {
            $arg = array();
            for ($i = 1; $i < func_num_args(); $i++) {
                $arg[] = func_get_arg($i);
            }
            $string = _gettext($string);

            return vsprintf($string, $arg);
        } else {
            return _gettext($string);
        }
    }
    function ev_ngettext($string, $plural, $number)
    {
        return _ngettext($string, $plural, $number);
    }
} else {
    function ev_gettext($string)
    {
        if (func_num_args() > 1) {
            $arg = array();
            for ($i = 1; $i < func_num_args(); $i++) {
                $arg[] = func_get_arg($i);
            }

            $string = gettext($string);

            return vsprintf($string, $arg);
        } else {
            return gettext($string);
        }
    }

    function ev_ngettext($string, $plural, $number)
    {
        return ngettext($string, $plural, $number);
    }
    function _bind_textdomain_codeset($domain, $codeset)
    {
        return bind_textdomain_codeset($domain, $codeset);
    }
    function _bindtextdomain($domain, $path)
    {
        return bindtextdomain($domain, $path);
    }
    function _textdomain($domain)
    {
        return textdomain($domain);
    }
    function _gettext($msgid)
    {
        return gettext($msgid);
    }
    function _setlocale($category, $locale)
    {
        return setlocale($category, $locale);
    }
}

// this won't change over the request. so set it once and permanently
_bindtextdomain('eventum', APP_PATH . '/localization/');
_bind_textdomain_codeset('eventum', APP_CHARSET);
_textdomain('eventum');
