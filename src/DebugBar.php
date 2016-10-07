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
        // disable debugbar in CLI
        if (PHP_SAPI == 'cli') {
            return;
        }

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
            'Smarty', [
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Smarty',
                'default' => '[]'
            ]
        );
        $renderer->addControl(
            'Config', [
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Config',
                'default' => '[]'
            ]
        );

        return $renderer;
    }
}
