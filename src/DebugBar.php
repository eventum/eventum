<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+


namespace Eventum;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DebugBar as BaseDebugBar;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use PDO;
use Setup;
use Smarty;
use User;

/**
 * Integration of PHP DebugBar
 *
 * @link http://phpdebugbar.com/
 * @package Eventum
 */
class DebugBar
{
    /** @var BaseDebugBar */
    private static $debugbar;

    /**
     * Create DebugBar instance
     */
    public static function initialize()
    {
        // setup debugbar, if it can be autoloaded
        if (!class_exists('DebugBar\StandardDebugBar')) {
            return;
        }

        self::$debugbar = new StandardDebugBar();
    }

    /**
     * Returns TRUE if DebugBar is available
     *
     * @return bool
     */
    public static function hasDebugBar()
    {
        return self::$debugbar !== null;
    }

    /**
     * @return BaseDebugBar
     */
    public static function getDebugBar()
    {
        return self::$debugbar;
    }

    /**
     * Get PDO proxy which traces statements for DebugBar
     *
     * @param PDO $pdo
     * @return TraceablePDO
     * @throws DebugBarException
     */
    public static function getTraceablePDO(PDO $pdo)
    {
        $debugbar = self::$debugbar;
        $pdo = new TraceablePDO($pdo);
        $debugbar->addCollector(new PDOCollector($pdo));

        return $pdo;
    }

    /**
     * Setup Debug Bar:
     * - if initialized
     * - if role_id is set
     * - if user is administrator
     *
     * @throws DebugBarException
     */
    public static function register(Smarty $smarty)
    {
        if (!self::$debugbar) {
            return;
        }

        $debugbarRenderer = self::getDebugBarRenderer($smarty);

        $smarty->assign('debugbar_head', $debugbarRenderer->renderHead());
        $smarty->assign('debugbar_body', $debugbarRenderer->render());
    }

    /**
     * Get DebugBar renderer, if it's first time called, add Smarty and Config tabs.
     *
     * @param Smarty $smarty
     * @return JavascriptRenderer
     * @throws DebugBarException
     */
    private static function getDebugBarRenderer(Smarty $smarty)
    {
        static $renderer;

        // the renderer can be created only once
        if ($renderer) {
            return $renderer;
        }

        $debugbar = self::$debugbar;
        $rel_url = APP_RELATIVE_URL;

        $debugbar->addCollector(
            new ConfigCollector($smarty->tpl_vars, 'Smarty')
        );
        $debugbar->addCollector(
            new ConfigCollector(Setup::get()->toArray(), 'Config')
        );

        $renderer = $debugbar->getJavascriptRenderer("{$rel_url}debugbar");
        $renderer->addControl(
            'Smarty', array(
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Smarty',
                'default' => '[]'
            )
        );
        $renderer->addControl(
            'Config', array(
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Config',
                'default' => '[]'
            )
        );

        return $renderer;
    }
}
