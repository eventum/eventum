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

use DebugBar\Bridge\DoctrineCollector;
use DebugBar\Bridge\MonologCollector;
use DebugBar\DataCollector\AggregatedCollector;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PDO\TraceablePDO;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;
use Eventum\Logger\LoggerTrait;
use Monolog\Logger as MonologLogger;
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
    use LoggerTrait;

    /** @var DebugBar */
    private $debugBar;

    public static function getDebugBarManager(): self
    {
        /** @var DebugBar */
        static $debugBar;

        return $debugBar ?: $debugBar = new self();
    }

    public function __construct()
    {
        // disable debugBar in CLI
        if (PHP_SAPI === 'cli') {
            return;
        }

        // setup debugVar, if it can be autoloaded
        if (!class_exists(StandardDebugBar::class)) {
            return;
        }

        $this->debugBar = new StandardDebugBar();

        $rel_url = Setup::getRelativeUrl();
        $this->debugBar->getJavascriptRenderer("{$rel_url}debugbar");
    }

    public function registerDoctrine(EntityManager $entityManager): void
    {
        if (!$this->debugBar) {
            return;
        }

        $debugBar = $this->debugBar;
        $debugStack = new DebugStack();
        $entityManager->getConnection()->getConfiguration()->setSQLLogger($debugStack);

        $debugBar->addCollector(new AggregatedCollector('doctrine'));
        $debugBar['doctrine']->addCollector(new DoctrineCollector($debugStack));
        $debugBar->getJavascriptRenderer()->addControl('Doctrine', [
            'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
            'map' => 'doctrine',
            'default' => '[]',
        ]);
    }

    public function registerMonolog(MonologLogger $logger): void
    {
        if (!$this->debugBar) {
            return;
        }

        $debugBar = $this->debugBar;

        if (!$debugBar->hasCollector('monolog')) {
            $debugBar->addCollector(new MonologCollector());
        }

        $debugBar['monolog']->addLogger($logger);
    }

    /**
     * Get PDO proxy which traces statements for DebugBar
     *
     * @param PDO $pdo
     * @throws DebugBarException
     * @return PDO
     */
    public function registerPdo(PDO $pdo): PDO
    {
        if ($this->debugBar) {
            $pdo = new TraceablePDO($pdo);
            $this->debugBar->addCollector(new PDOCollector($pdo));
        }

        return $pdo;
    }

    public function registerSmarty(Smarty $smarty): void
    {
        if (!$this->debugBar) {
            return;
        }

        try {
            $renderer = $this->getDebugBarRenderer($smarty);
            $smarty->assign('debugbar_head', $renderer->renderHead());
            $smarty->assign('debugbar_body', $renderer->render());
        } catch (DebugBarException $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Get DebugBar renderer, if it's first time called, add Smarty and Config tabs.
     *
     * @param Smarty $smarty
     * @throws DebugBarException
     * @return JavascriptRenderer
     */
    private function getDebugBarRenderer(Smarty $smarty): JavascriptRenderer
    {
        $debugBar = $this->debugBar;
        $renderer = $debugBar->getJavascriptRenderer();

        $debugBar->addCollector(
            new ConfigCollector($smarty->tpl_vars, 'Smarty')
        );
        $debugBar->addCollector(
            new ConfigCollector(ServiceContainer::getConfig()->toArray(), 'Config')
        );

        $renderer->addControl(
            'Smarty',
            [
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Smarty',
                'default' => '[]',
            ]
        );
        $renderer->addControl(
            'Config',
            [
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'Config',
                'default' => '[]',
            ]
        );

        return $renderer;
    }
}
