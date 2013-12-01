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

class Language
{

    /**
     * Method used to set application default locale.
     *
     * @return  void
     */
    public static function setup()
    {
        // please add the following line to config.inc.php, changing to whatever language you prefer
        // define('APP_DEFAULT_LOCALE', 'en_US');

        ini_set('mbstring.internal_encoding', 'UTF8');

        self::set(APP_DEFAULT_LOCALE);
    }

    /**
     * List of available locales present in Eventum.
     *
     * Note that the locales are first tested before they are listed as choices in
     * Preferences page.
     */
    private static $avail_langs = array(
        'br' => 'Breton',
        'ca' => 'Catalan',
        'cs' => 'Czech',
        'da_DK' => 'Danish',
        'de_DE' => 'German',
        'en_AU' => 'English (Australia)',
        'en_CA' => 'English (Canada)',
        'en_GB' => 'English (United Kingdom)',
        'eo' => 'Esperanto',
        'es_ES' => 'Spanish',
        'et_EE' => 'Estonian',
        'fi_FI' => 'Finnish',
        'fo' => 'Faroese',
        'fr_FR' => 'French',
        'he_IL' => 'Hebrew',
        'ht' => 'Haitian',
        'hu' => 'Hungarion',
        'id' => 'Indonesian',
        'it_IT' => 'Italian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'lt_LT' => 'Lithuanian',
        'lv_LV' => 'Latvian',
        'nl_NL' => 'Dutch',
        'oc' => 'Occitan (post 1500)',
        'pl_PL' => 'Polish',
        'pt' => 'Portuguese',
        'pt_BR' => 'Brazilian Portuguese',
        'ru_RU' => 'Russian',
        'si' => 'Sinhalese',
        'sv_SE' => 'Swedish',
        'ta' => 'Tamil',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'vi' => 'Vietnamese',
        'zh_CN' => 'Chinese (Simplified)',
    );

    /**
     * Method used to get available languages.
	 * Uses $avail_langs array and verifies that the language can be used.
     *
     * @access  public
     * @return  array
     */
    function getAvailableLanguages()
    {
		$languages = array();
		foreach (self::$avail_langs as $code => $language) {
			$res = self::set($code);
			if ($res) {
				$languages[$code] = $language;
			}
		}

		self::restore();
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
			if (self::set($usr_lang)) {
				$lang = $usr_lang;
			}
        }

		if ($lang == null) {
			// fall back to system default
			define('APP_CURRENT_LOCALE', APP_DEFAULT_LOCALE);
			// we don't need to set language again as APP_DEFAULT_LOCALE was set by self::setup()
			// self::set(APP_CURRENT_LOCALE);
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
	public static function set($locale)
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
        User::resetLocalizedRoles();

		return true;
    }


    public static function restore()
    {
		$locale = defined('APP_CURRENT_LOCALE') ? APP_CURRENT_LOCALE : APP_DEFAULT_LOCALE;
		self::set($locale);
    }
}
