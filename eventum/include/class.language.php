<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.class.language.php 1.10 03/12/31 17:29:00-00:00 jpradomaia $
//


/**
 * Class to handle the logic behind the internationalization issues
 * of the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

// this will eventually be used to support more than one language
$avail_langs = array(
    "en"
);
@define("APP_DEFAULT_LANG" , "en");

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
        global $HTTP_GET_VARS, $HTTP_SESSION_VARS, $app_lang, $avail_langs;

        @session_start();
        if (!empty($HTTP_GET_VARS["lang"])) {
            session_register("app_lang");
            if (!in_array($HTTP_GET_VARS["lang"], $avail_langs)) {
                $app_lang = APP_DEFAULT_LANG;
            } else {
                $app_lang = $HTTP_GET_VARS["lang"];
            }
        }
        if (empty($app_lang)) {
            $app_lang = APP_DEFAULT_LANG;
        }
        @define("APP_CURRENT_LANG", $app_lang);
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Language Class');
}
?>
