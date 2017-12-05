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

use Eventum\Event;

/**
 * Example Eventum Extension.
 *
 * To enable this extension, write to config/setup.php:
 *
 * 'extensions' => [
 *   'Eventum\\Extension\\ExampleExtension' => '/path/to/this/file/ExampleExtension.php',
 * ],
 *
 * Class doc comment will be used to describe purpose or add documentation.
 */
class ExampleExtension extends AbstractExtension
{
    /**
     * Method invoked so the extension can setup class loader.
     *
     * @param \Composer\Autoload\ClassLoader $loader
     */
    public function registerAutoloader($loader)
    {
        $phpDir = '/usr/share/php';
        $baseDir = dirname(dirname(dirname(__DIR__)));

        $classmap = [
            'example_Workflow_Backend' => $baseDir . '/src/Workflow/example_Workflow_Backend.php',
        ];
        $psr0 = [
            'Pimple\\' => $phpDir,
        ];
        $psr4 = [
            'Eventum\\Event\\' => [$baseDir . '/docs/examples/extension/Event'],
        ];

        $loader->addClassMap($classmap);

        foreach ($psr0 as $namespace => $path) {
            $loader->add($namespace, $path);
        }

        foreach ($psr4 as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }
    }

    /**
     * Return list of workflow classes.
     *
     * @return string[]
     */
    public function getAvailableWorkflows()
    {
        return [
            /*
            'example_Workflow_Backend',
            */
        ];
    }

    /**
     * Return list of custom field classes.
     *
     * @return string[]
     */
    public function getAvailableCustomFields()
    {
        return [
        ];
    }

    /**
     * Return list of partner classes.
     *
     * @return string[]
     */
    public function getAvailablePartners()
    {
        return [
            /*
            'PartnerBackend',
            'Example\\PartnerX',
            */
        ];
    }

    /**
     * Return list of CRM classes.
     *
     * @return string[]
     */
    public function getAvailableCRMs()
    {
        return [
            /*
            'Example\\CRM',
            */
        ];
    }

    /**
     * Get classes implementing EventSubscriberInterface.
     *
     * @see http://symfony.com/doc/current/components/event_dispatcher.html#using-event-subscribers
     * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface
     * @return string[]
     */
    public function getSubscribers()
    {
        return [
            Event\CryptoSubscriber::class,
            Event\HistorySubscriber::class,
            Event\UserSubscriber::class,
            Event\CommitSubscriber::class,
        ];
    }
}
