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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Eventum\Extension\ExtensionManager;
use Eventum\ServiceContainer;

/**
 * https://symfony.com/doc/4.4/components/dependency_injection.html
 */
return static function (ContainerConfigurator $configurator) {
    /** @var ExtensionManager $em */
    $em = ServiceContainer::get(ExtensionManager::class);
    $em->containerConfigurator($configurator);
};
