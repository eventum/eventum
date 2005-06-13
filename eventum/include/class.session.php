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
// @(#) $Id$
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.setup.php");

/**
 * Wrapper class for sessions. This is an initial bare bones implementation.
 * Additional methods will be later as needed.
 *
 * @version 1.0
 * @author Bryan Alsdorf <bryan@mysql.com>
 */

class Session
{
    /**
     * Sets the passed variable in the session using the specified name.
     * 
     * @access  public
     * @param   string $name Name to store variable under.
     * @param   mixed $var Variable to store in session.
     */
    function set($name, $var)
    {
        GLOBAL $HTTP_SESSION_VARS;
        $HTTP_SESSION_VARS[$name] = $var;
    }
    
    
    /**
     * Returns the session variable specified by $name
     * 
     * @access  public
     * @param   string $name The name of variable to be returned.
     * @return  mixed The session variable.
     */
    function get($name)
    {
        GLOBAL $HTTP_SESSION_VARS;
        return @$HTTP_SESSION_VARS[$name];
    }
    
    
    /**
     * Returns true if the session variable $name is set, false otherwise.
     * 
     * @access  public
     * @param   string $name The name of the variable to check.
     * @return  boolean If the variable is set
     */
    function is_set($name)
    {
        GLOBAL $HTTP_SESSION_VARS;
        return isset($HTTP_SESSION_VARS[$name]);
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Session Class');
}
?>
