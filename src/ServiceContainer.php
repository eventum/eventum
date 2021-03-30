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

use Doctrine\ORM\EntityManagerInterface;
use Eventum\Config\Config;
use Eventum\EventDispatcher\EventManager;
use Eventum\Extension\ExtensionManager;
use LogicException;
use Pimple\Container;
use Pimple\Psr11\Container as PsrContainer;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ServiceContainer
{
    public static function getInstance(): Container
    {
        static $container;

        if (!$container) {
            $container = new Container();
            $container->register(new ServiceProvider\ServiceProvider());
            $container->register(new ServiceProvider\EventDispatcherService());
            $container->register(new ServiceProvider\FulltextSearchService());
            $container->register(new ServiceProvider\MarkdownServiceProvider());
            $container->register(new ServiceProvider\ConsoleCommandsService());
        }

        return $container;
    }

    /**
     * @since 3.8.13
     */
    public static function getContainer(): ContainerInterface
    {
        static $container;

        if (!$container) {
            $container = new PsrContainer(self::getInstance());
        }

        return $container;
    }

    /**
     * @since 3.8.11
     */
    public static function get(string $className)
    {
        return static::getInstance()[$className];
    }

    /**
     * @since 3.8.11
     */
    public static function getConfig(): Config
    {
        return static::get('config');
    }

    /**
     * @since 3.9.3
     */
    public static function getLogger(): LoggerInterface
    {
        return static::get(LoggerInterface::class);
    }

    /**
     * @since 3.9.8
     */
    public static function getRequest(): Request
    {
        return static::get(Request::class);
    }

    /**
     * @since 3.9.3
     */
    public static function getKernel(): KernelInterface
    {
        return static::get(KernelInterface::class);
    }

    /**
     * @since 3.9.3
     */
    public static function getApplication(): Application
    {
        return static::get(Application::class);
    }

    /**
     * @since 3.9.3
     */
    public static function getEntityManager(): EntityManagerInterface
    {
        if (static::isBooting()) {
            throw new LogicException('Access to EntityManagerInterface forbidden when Kernel is booting');
        }

        return static::get(EntityManagerInterface::class);
    }

    /**
     * @since 3.10.2
     */
    public static function getEventDispatcher(): EventDispatcherInterface
    {
        return EventManager::getEventDispatcher(false);
    }

    /**
     * Helper to dispatch events
     *
     * @param string $eventName
     * @param Event|\Symfony\Component\EventDispatcher\Event $event
     * @return Event|object
     * @see EventDispatcherInterface::dispatch()
     * @since 3.10.2
     */
    public static function dispatch(string $eventName, $event = null)
    {
        return static::getEventDispatcher()->dispatch($event ?? new Event(), $eventName);
    }

    /**
     * @since 3.10.2
     */
    public static function getExtensionManager(): ExtensionManager
    {
        return static::get(ExtensionManager::class);
    }

    /**
     * @since 3.10.2
     */
    private static function isBooting(): bool
    {
        return static::getKernel()->isBooting();
    }
}
