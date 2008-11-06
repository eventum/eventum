<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007, 2008 MySQL AB            |
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

/**
 * Class to handle the logic behind the internationalization issues
 * of the application.
 *
 * @version 1.0
 * @author Bryan Alsdorf <bryan@mysql.com>
 */

/**
 * List of available locales present in Eventum.
 *
 * Note that the locales are first tested before they are listed as choices in
 * Preferences page.
 */

$avail_langs = array(
    'pl_PL' =>  'Polish',
#    'en_US' =>  'English',
    'ru_RU' =>  'Russian',
    'de_DE' =>  'German',
#    'fr_FR' =>  'French',
    'it_IT' =>  'Italian',
    'fi_FI' =>  'Finnish',
    'es_ES' =>  'Spanish',
#    'nl_NL' =>  'Dutch',
    'sv_SE' =>  'Swedish',
    'pt_BR' =>  'Brazilian Portuguese',
);

class Language
{

    /**
     * Method used to set application default locale.
     *
     * @access  public
     * @return  void
     */
    function setup()
    {
        // please add the following line to config.inc.php, changing to whatever language you prefer
        // define('APP_DEFAULT_LOCALE', 'en_US');

        ini_set('mbstring.internal_encoding', 'UTF8');

        Language::set(APP_DEFAULT_LOCALE);
    }

    /**
     * Method used to get available languages.
	 * Uses $avail_langs array and verifies that the language can be used.
     *
     * @access  public
     * @return  array
     */
    function getAvailableLanguages()
    {
        global $avail_langs;

		$languages = array();
		foreach ($avail_langs as $code => $language) {
			$res = Language::set($code);
			if ($res) {
				$languages[$code] = $language;
			}
		}

		Language::restore();
		return $languages;
    }

    /**
     * Method used to set the appropriate preference of the language
     * for the application based on user preference.
     *
     * @access  public
     * @return  void
     */
    function setPreference()
    {
        $usr_id = Auth::getUserID();
		$lang = null;
        if (!empty($usr_id)) {
			// try user preference
            $usr_lang = User::getLang($usr_id);
			if (Language::set($usr_lang)) {
				$lang = $usr_lang;
			}
        }

		if ($lang == null) {
			// fall back to system default
			define('APP_CURRENT_LOCALE', APP_DEFAULT_LOCALE);
			// we don't need to set language again as APP_DEFAULT_LOCALE was set by Language::setup()
			// Language::set(APP_CURRENT_LOCALE);
		} else {
			define('APP_CURRENT_LOCALE', $lang);
		}
    }

    /**
     * Sets active language for for the application.
	 * Returns false if locale is invalid or cannot be used.
     *
     * @access  public
     * @return  boolean
     */
	function set($locale)
    {
		// XXX do not append charset to en_US locale
		if ($locale != 'en_US') {
			$locale = $locale . '.' . APP_CHARSET;
		}
		$res = _setlocale(LC_TIME, $locale);
		if ($res === false) {
			return false;
		}

		$res = _setlocale(LC_MESSAGES, $locale);
		if ($res === false) {
			return false;
		}

		// XXX do not require translations for en_US locale
		if ($locale != 'en_US') {
			// get translator info
			$res = _gettext('');
			// if empty gettext is returned then the mo catalog is not installed.
			if (empty($res)) {
				return false;
			}
		}
        User::setLocalizedRoles();

		return true;
    }


    function restore()
    {
		$locale = defined('APP_CURRENT_LOCALE') ? APP_CURRENT_LOCALE : APP_DEFAULT_LOCALE;
		Language::set($locale);
    }
}

// if there is no gettext support built into PHP, or we are running in language compatability mode include PHP-gettext
if (!function_exists('gettext') || (defined('APP_GETTEXT_MODE') && APP_GETTEXT_MODE == 'php')) {
    require_once(APP_INC_PATH . 'php-gettext/gettext.inc');

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
_bindtextdomain('eventum', APP_PATH . 'misc/localization/');
_bind_textdomain_codeset('eventum', APP_CHARSET);
_textdomain('eventum');

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Language Class');
}
