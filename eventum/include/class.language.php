<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                              |
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
// +----------------------------------------------------------------------+
//

// the list of available languges is in config.inc.php

/**
 * Class to handle the logic behind the internationalization issues
 * of the application.
 *
 * @version 1.0
 * @author Bryan Alsdorf <bryan@mysql.com>
 */

class Language
{

    function setup()
    {
        // please add the following line to config.inc.php, changing to whatever language you prefer
        // define('APP_DEFAULT_LOCALE', 'en_US');

        ini_set('mbstring.internal_encoding', 'UTF8');

        Language::set(APP_DEFAULT_LOCALE);
    }

    /**
     * Method used to set the appropriate preference of the language
     * for the application.
     *
     * @access  public
     * @return  void
     */
    function setPreference()
    {
        global $avail_langs;

        $usr_id = Auth::getUserID();
        if (empty($usr_id)) {
            define('APP_CURRENT_LOCALE', APP_DEFAULT_LOCALE);
        } else {
            $usr_lang = User::getLang($usr_id);
            if (!in_array($usr_lang, array_keys($avail_langs))) {
                $usr_lang = APP_DEFAULT_LOCALE;
            }
            define('APP_CURRENT_LOCALE', $usr_lang);
        }
        Language::set(APP_CURRENT_LOCALE);
    }


    function set($locale)
    {
        setlocale(LC_TIME, $locale . '.UTF8', $locale);
        if (APP_GETTEXT_MODE == 'native') {
            setlocale(LC_MESSAGES, $locale . '.UTF8', $locale);
            bindtextdomain("eventum", APP_PATH . "misc/localization/");
            bind_textdomain_codeset("eventum", APP_CHARSET);
            textdomain("eventum");
        } elseif (APP_GETTEXT_MODE == 'php') {
            _setlocale(LC_MESSAGES, $locale);
            _bindtextdomain("eventum", APP_PATH . "misc/localization/");
            _bind_textdomain_codeset("eventum", APP_CHARSET);
            _textdomain("eventum");
        }

        User::setLocalizedRoles();
    }


    function restore()
    {
        Language::set((defined('APP_CURRENT_LOCALE') ? APP_CURRENT_LOCALE : APP_DEFAULT_LOCALE));
    }
}


// helper function to help with translating strings with variables in them
function ev_gettext($string)
{
    if (func_num_args() > 1) {
        $arg = array();
        for($i = 1 ; $i < func_num_args(); $i++) {
            $arg[] = func_get_arg($i);
        }

        if (APP_GETTEXT_MODE == 'php') {
            $string = _gettext($string);
        } else {
            $string = gettext($string);
        }

        return vsprintf($string, $arg);
    } else {
        if (APP_GETTEXT_MODE == 'php') {
            return _gettext($string);
        } else {
            return gettext($string);
        }
    }
}


function ev_ngettext($string, $plural, $number)
{
    if (APP_GETTEXT_MODE == 'php') {
        return _ngettext($string, $plural, $number);
    } else {
        return ngettext($string, $plural, $number);
    }
}


// if there is no gettext support built into PHP, or we are running in language compatability mode include PHP-gettext
if ((!function_exists('gettext')) || ((defined('APP_GETTEXT_MODE')) && (APP_GETTEXT_MODE == 'php'))) {
    require_once(APP_INC_PATH . "php-gettext/gettext.inc");
    @define('APP_GETTEXT_MODE', 'php');
} else {
    @define('APP_GETTEXT_MODE', 'native');
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Language Class');
}
