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
use Pimple\Container;
use Pimple\Psr11\Container as PsrContainer;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

class ServiceContainer
{
    public static function getInstance(): Container
    {
        static $container;

        if (!$container) {
            $container = new Container();
            $container->register(new ServiceProvider\ServiceProvider());
            $container->register(new ServiceProvider\FulltextSearchService());
            $container->register(new ServiceProvider\MarkdownServiceProvider());
            $container->register(new ServiceProvider\ConsoleCommandsService());
        }

        return $container;
    }

    public static function getContainer(): ContainerInterface
    {
        static $container;

        if (!$container) {
            $container = new PsrContainer(self::getInstance());
        }

        return $container;
    }

    public static function get(string $className)
    {
        return static::getInstance()[$className];
    }

    public static function getConfig(): Config
    {
        return static::get('config');
    }

    public static function getLogger(): LoggerInterface
    {
        return static::get(LoggerInterface::class);
    }

    public static function getKernel(): KernelInterface
    {
        return static::get(KernelInterface::class);
    }

    public static function getApplication(): Application
    {
        return static::get(Application::class);
    }

    public static function getEntityManager(): EntityManagerInterface
    {
        return static::get(EntityManagerInterface::class);
    }
}
