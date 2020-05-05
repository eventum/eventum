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

namespace Eventum\ServiceProvider;

use DB_Helper;
use Eventum\Extension\ExtensionManager;
use Eventum\Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Setup;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        $app['logger'] = static function () {
            return Logger::app();
        };

        $app['config'] = static function () {
            return Setup::get();
        };

        $app['db'] = static function () {
            return DB_Helper::getInstance();
        };

        $app[ExtensionManager::class] = static function () {
            return ExtensionManager::getManager();
        };
    }
}
