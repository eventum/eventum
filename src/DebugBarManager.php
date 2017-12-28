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
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Eventum\Monolog\Logger;
use PDO;
use Setup;
use Smarty;

/**
 * Integration of PHP DebugBar
 *
 * @see http://phpdebugbar.com/
 */
class DebugBarManager
{
    /** @var DebugBar */
    private static $debugBar;

    /**
     * Create DebugBar instance
     */
    public static function initialize()
    {
        // disable debugBar in CLI
        if (PHP_SAPI === 'cli') {
            return;
        }

        // setup debugVar, if it can be autoloaded
        if (!class_exists(StandardDebugBar::class)) {
            return;
        }

        self::$debugBar = new StandardDebugBar();
    }

    /**
     * Returns TRUE if DebugBar is available
     *
     * @return bool
     */
    public static function hasDebugBar()
    {
        return self::$debugBar !== null;
    }

    /**
     * Get PDO proxy which traces statements for DebugBar
     *
     * @param PDO $pdo
     * @throws DebugBarException
     * @return TraceablePDO
     */
    public static function getTraceablePDO(PDO $pdo)
    {
        $pdo = new TraceablePDO($pdo);
        self::$debugBar->addCollector(new PDOCollector($pdo));

        return $pdo;
    }

    public static function register(Smarty $smarty)
    {
        if (!self::$debugBar) {
            return;
        }

        try {
            $renderer = self::getDebugBarRenderer($smarty);
            $smarty->assign('debugbar_head', $renderer->renderHead());
            $smarty->assign('debugbar_body', $renderer->render());
        } catch (DebugBarException $e) {
            Logger::app()->error($e->getMessage());
        }
    }

    /**
     * Get DebugBar renderer, if it's first time called, add Smarty and Config tabs.
     *
     * @param Smarty $smarty
     * @throws DebugBarException
     * @return JavascriptRenderer
     */
    private static function getDebugBarRenderer(Smarty $smarty)
    {
        static $renderer;

        // the renderer can be created only once
        if ($renderer) {
            return $renderer;
        }

        $debugBar = self::$debugBar;
        $rel_url = APP_RELATIVE_URL;

        $debugBar->addCollector(
            new ConfigCollector($smarty->tpl_vars, 'Smarty')
        );
        $debugBar->addCollector(
            new ConfigCollector(Setup::get()->toArray(), 'Config')
        );

        $renderer = $debugBar->getJavascriptRenderer("{$rel_url}debugbar");
        $renderer->addControl(
            'Smarty', [
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Smarty',
                'default' => '[]',
            ]
        );
        $renderer->addControl(
            'Config', [
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Config',
                'default' => '[]',
            ]
        );

        return $renderer;
    }
}
