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

namespace Eventum\Extension\Provider;

use Eventum\Extension\ClassLoader;

interface AutoloadProvider extends ExtensionProvider
{
    /**
     * Method invoked so the extension can setup class loader.
     *
     * @param ClassLoader $loader
     * @since 3.6.6
     * @since 3.8.1 $loader is decorator over composer ClassLoader
     */
    public function registerAutoloader($loader): void;
}
