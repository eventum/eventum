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

namespace Eventum\Extension;

use Eventum\Controller\ScmPingController;
use Eventum\Extension\Provider\ExtensionProvider;
use Eventum\Extension\Provider\RouteProvider;
use Eventum\Logger\LoggerTrait;
use Eventum\ServiceContainer;
use Symfony\Component\Routing\RouteCollectionBuilder;

class ScmExtension implements
    ExtensionProvider,
    RouteProvider
{
    use LoggerTrait;

    /** @var bool */
    private $enabled;

    public function __construct()
    {
        $config = ServiceContainer::getConfig();
        $this->enabled = $config['scm_integration'] === 'enabled';
    }

    public function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes
            ->add('/scm_ping.php', ScmPingController::class . '::defaultAction', 'scm_ping')
            ->setMethods('POST');
    }
}
