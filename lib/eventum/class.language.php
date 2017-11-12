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

/**
 * Class to handle the logic behind the internationalization issues
 * of the application.
 */
class Language
{
    /**
     * Return true if our APP_CHARSET is UTF8
     * @return bool
     */
    public static function isUTF8()
    {
        return strtolower(APP_CHARSET) == 'utf-8' || strtolower(APP_CHARSET) == 'utf8';
    }

    /**
     * Init encodings: iconv, mbstring
     */
    private static function initEncoding()
    {
        ini_set('input_encoding', APP_CHARSET);
        ini_set('output_encoding', APP_CHARSET);
        ini_set('default_charset', APP_CHARSET);
    }

    /**
     * Method used to set application default locale.
     */
    public static function setup()
    {
        self::set(APP_DEFAULT_LOCALE);
        self::initEncoding();
    }

    /**
     * List of available locales present in Eventum.
     *
     * Note that the locales are first tested before they are listed as choices in
     * Preferences page.
     */
    private static $avail_langs = [
        'br_FR' => 'Breton',
        'ca_ES' => 'Catalan',
        'cs_CZ' => 'Czech',
        'da_DK' => 'Danish',
        'de_DE' => 'German',
        'en_AU' => 'English (Australia)',
        'en_CA' => 'English (Canada)',
        'en_GB' => 'English (United Kingdom)',
        'eo' => 'Esperanto',
        'es_ES' => 'Spanish',
        'et_EE' => 'Estonian',
        'fi_FI' => 'Finnish',
        'fo_FO' => 'Faroese',
        'fr_FR' => 'French',
        'he_IL' => 'Hebrew',
        'ht_HT' => 'Haitian',
        'hu_HU' => 'Hungarion',
        'id_ID' => 'Indonesian',
        'it_IT' => 'Italian',
        'ja_JP' => 'Japanese',
        'ko_KR' => 'Korean',
        'lt_LT' => 'Lithuanian',
        'lv_LV' => 'Latvian',
        'nl_NL' => 'Dutch',
        'oc_FR' => 'Occitan (post 1500)',
        'pl_PL' => 'Polish',
        'pt_PT' => 'Portuguese',
        'pt_BR' => 'Brazilian Portuguese',
        'ru_RU' => 'Russian',
        'si_LK' => 'Sinhalese',
        'sv_SE' => 'Swedish',
        'ta_IN' => 'Tamil',
        'th_TH' => 'Thai',
        'tr_TR' => 'Turkish',
        'uk_UA' => 'Ukrainian',
        'ur_IN' => 'Urdu',
        'vi_VN' => 'Vietnamese',
        'zh_CN' => 'Chinese (Simplified)',
    ];

    /**
     * Method used to get available languages.
     * If $validate is true the languages are verified first.
     *
     * @param bool $validate
     * @return array
     */
    public static function getAvailableLanguages($validate = true)
    {
        if (!$validate) {
            return self::$avail_langs;
        }

        $languages = [];
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
     */
    public static function setPreference()
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
     * @return  bool
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
