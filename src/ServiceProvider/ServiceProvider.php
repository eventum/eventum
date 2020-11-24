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
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Eventum\DebugBarManager;
use Eventum\EventDispatcher\EventManager;
use Eventum\Extension\ExtensionManager;
use Eventum\Kernel;
use Eventum\Mail\MessageIdGenerator;
use Eventum\Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Log\LoggerInterface;
use Setup;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

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

        $app[EntityManagerInterface::class] = static function ($app) {
            /** @var ContainerInterface $container */
            $container = $app[ContainerInterface::class];

            $em = $container->get(EntityManagerInterface::class);
            DebugBarManager::getDebugBarManager()->registerDoctrine($em);

            return $em;
        };

        $app[Connection::class] = static function ($app) {
            return $app[EntityManagerInterface::class]->getConnection();
        };

        $app[KernelInterface::class] = static function () {
            return new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
        };

        $app[Request::class] = static function () {
            return Request::createFromGlobals();
        };

        $app[Application::class] = static function ($app) {
            $kernel = $app[KernelInterface::class];

            $application = new Application($kernel);
            $application->setAutoExit(false);

            return $application;
        };

        $app[ContainerInterface::class] = static function ($app) {
            /** @var KernelInterface $kernel */
            $kernel = $app[KernelInterface::class];

            return $kernel->ensureBooted()->getContainer();
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

        $app[MessageIdGenerator::class] = static function () {
            return new MessageIdGenerator(Setup::getHostname());
        };
    }
}
