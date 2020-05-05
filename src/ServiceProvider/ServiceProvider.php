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
use Eventum\Config\SphinxConfig;
use Eventum\EventDispatcher\EventManager;
use Eventum\Extension\ExtensionManager;
use Eventum\Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Log\LoggerInterface;
use Setup;
use SphinxClient;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        $app['logger'] = static function ($app) {
            return $app[LoggerInterface::class];
        };

        $app['config'] = static function () {
            return Setup::get();
        };

        $app['db'] = static function () {
            return DB_Helper::getInstance();
        };

        $app[LoggerInterface::class] = static function () {
            return Logger::app();
        };

        $app[EventDispatcherInterface::class] = static function () {
            return EventManager::getEventDispatcher();
        };

        $app[ExtensionManager::class] = static function () {
            return ExtensionManager::getManager();
        };

        $app[SphinxConfig::class] = static function ($app) {
            return new SphinxConfig($app['config']);
        };

        $app[SphinxClient::class] = static function ($app) {
            /** @var SphinxConfig $config */
            $config = $app[SphinxConfig::class];

            $sphinx = new SphinxClient();
            $sphinx->SetServer($config->host, $config->port);

            return $sphinx;
        };
    }
}
