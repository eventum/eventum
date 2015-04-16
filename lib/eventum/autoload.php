<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2009 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Autoload class for Eventum.
 */
class Eventum_Autoload
{
    private static $excludes = array('.', '..', '.svn', 'CVS');
    private static $classes;

    protected static function getMap()
    {
        $baseDir = APP_INC_PATH;
        $pearDir = APP_PEAR_PATH;

        return array(
            'DB' => $pearDir . '/DB.php',
            'DB_Error' => $pearDir . '/DB.php',
            'DB_common' => $pearDir . '/DB/common.php',
            'DB_mysql' => $pearDir . '/DB/mysql.php',
            'DB_mysqli' => $pearDir . '/DB/mysqli.php',
            'Date' => $pearDir . '/Date.php',
            'Date_Calc' => $pearDir . '/Date/Calc.php',
            'Date_Span' => $pearDir . '/Date/Span.php',
            'Date_TimeZone' => $pearDir . '/Date/TimeZone.php',
            'Mail' => $pearDir . '/Mail.php',
            'Mail_RFC822' => $pearDir . '/Mail/RFC822.php',
            'Mail_mime' => $pearDir . '/Mail/mime.php',
            'Mail_mimeDecode' => $pearDir . '/Mail/mimeDecode.php',
            'Math_Stats' => $pearDir . '/Math/Stats.php',
            'Net_LDAP2' => $pearDir . '/Net/LDAP2.php',
            'PEAR5' => $pearDir . '/PEAR5.php',
            'PEAR_Error' => $pearDir . '/PEAR.php',
            'Text_Diff' => $pearDir . '/Text/Diff.php',
            'Text_Diff_Renderer' => $pearDir . '/Text/Diff/Renderer.php',
            'Text_Diff_Renderer_unified' => $pearDir . '/Text/Diff/Renderer/unified.php',
            'XML_RPC_Server' => $pearDir . '/XML/RPC/Server.php',

            'Smarty' => APP_SMARTY_PATH . '/Smarty.class.php',
            'SphinxClient' => APP_SPHINXAPI_PATH . '/sphinxapi.php',

            'DbPear' => $baseDir . '/db/DbPear.php',
            'DbInterface' => $baseDir . '/db/DbInterface.php',
            'RemoteApi' => $baseDir . '/rpc/RemoteApi.php',
            'XmlRpcServer' => $baseDir . '/rpc/XmlRpcServer.php',
            'PlotHelper' => $baseDir . '/PlotHelper.php',

            'Auth_Backend_Interface' => $baseDir . '/auth/class.auth_backend_interface.php',
            'Mysql_Auth_Backend' => $baseDir . '/auth/class.mysql_auth_backend.php',
            'LDAP_Auth_Backend' => $baseDir . '/auth/class.ldap_auth_backend.php',

            'Abstract_Fulltext_Search' => $baseDir . '/search/class.abstract_fulltext_search.php',
            'MySQL_Fulltext_Search' => $baseDir . '/search/class.mysql_fulltext_search.php',
            'Sphinx_Fulltext_Search' => $baseDir . '/search/class.sphinx_fulltext_search.php',
        );
    }

    public static function autoload($className)
    {
        $classMap = self::getMap();

        if (isset($classMap[$className])) {
            require_once $classMap[$className];

            return;
        }

        // Eventum own classes
        if (!is_array(self::$classes)) {
            self::$classes = array();
            self::scan(dirname(__FILE__));
        }

        $className = strtolower($className);
        if (array_key_exists($className, self::$classes)) {
            require_once self::$classes[$className];

            return;
        }

        return;
    }

    private static function scan($path)
    {
        $dh = opendir($path);
        if ($dh === false) {
            return;
        }

        while (($file = readdir($dh)) !== false) {
            // omit exclusions
            if (array_search($file, self::$excludes) !== false) {
                continue;
            }

            // exclude hidden paths
            if ($file[0] == '.') {
                continue;
            }

            // recurse
            if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                self::scan($path . DIRECTORY_SEPARATOR . $file);
                continue;
            }

            // skip without .php extension
            if (substr($file, -4) != '.php') {
                continue;
            }
            $class = substr($file, 0, -4);

            // rewrite from class.CLASSNAME.php
            if (substr($class, 0, 6) === 'class.') {
                $class = strtolower(substr($class, 6));

                self::$classes[$class] = $path . DIRECTORY_SEPARATOR . $file;
            }
        }
        closedir($dh);
    }
}

if (function_exists('spl_autoload_register')) {
    spl_autoload_register(array('Eventum_Autoload', 'autoload'));
} else {
    function __autoload($className)
    {
        Eventum_Autoload::autoload($className);
    }
}
