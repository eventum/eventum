<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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

// this will eventually be used to support more than one language
$avail_langs = array(
    "en_US",
    "ru_RU",
    "de_DE",
    "fr_FR",
    "it_IT",
    "fi_FI",
    "es_ES",
    "nl_NL"
);

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
     * Method used to set the appropriate preference of the language
     * for the application.
     *
     * @access  public
     * @return  void
     */
    function setPreference()
    {
        global $HTTP_GET_VARS, $HTTP_SESSION_VARS, $avail_langs;

        // please add the following line to config.inc.php, changing to whatever language you prefer
        // define('APP_DEFAULT_LOCALE', 'en_US');

        define('APP_CURRENT_LOCALE', APP_DEFAULT_LOCALE);
        $new_locale = setlocale(LC_TIME, APP_CURRENT_LOCALE . '.UTF8', APP_CURRENT_LOCALE);
        $new_locale = setlocale(LC_MESSAGES, APP_CURRENT_LOCALE . '.UTF8', APP_CURRENT_LOCALE);
        bindtextdomain("eventum", APP_PATH . "misc/localization/");
        bind_textdomain_codeset("eventum", APP_CHARSET);
        textdomain("eventum");


        ini_set('mbstring.internal_encoding', 'UTF8');
    }
}


// helper function to help with translating strings with variables in them
function ev_gettext($string)
{
    $arg = array();
   for($i = 1 ; $i < func_num_args(); $i++)
       $arg[] = func_get_arg($i);

   return vsprintf(gettext($string), $arg);
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Language Class');
}
?>
