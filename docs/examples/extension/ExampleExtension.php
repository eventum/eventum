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

namespace Example;

use Eventum\Extension\ClassLoader;
use Eventum\Extension\Provider;
use Example\Auth\NullAuthAdapter;

/**
 * Example Eventum Extension.
 *
 * To enable this extension, write to config/setup.php:
 *
 * 'extensions' => [
 *   'Eventum\\Extension\\ExampleExtension' => '/path/to/this/file/ExampleExtension.php',
 * ],
 */
class ExampleExtension implements
    Provider\AutoloadProvider,
    Provider\CrmProvider,
    Provider\CustomFieldProvider,
    Provider\AuthAdapterProvider,
    Provider\PartnerProvider,
    Provider\SubscriberProvider,
    Provider\WorkflowProvider
{
    /**
     * Path to the root of this Extension
     */
    public const EXTENSION_DIR = __DIR__;

    /**
     * Method invoked so the extension can setup class loader.
     *
     * @param ClassLoader $loader
     */
    public function registerAutoloader($loader): void
    {
        $phpDir = '/usr/share/php';
        $baseDir = dirname(__DIR__, 3);

        $classmap = [
            'example_Workflow_Backend' => $baseDir . '/src/Workflow/example_Workflow_Backend.php',
        ];
        $psr0 = [
            'Pimple\\' => $phpDir,
        ];
        $psr4 = [
            'Example\\' => [$baseDir . '/docs/examples/extension'],
        ];

        $files = [
            // the fileIdentifier is md5(package.name:file.path):
            // https://github.com/composer/composer/blob/1.9.0/src/Composer/Autoload/AutoloadGenerator.php#L914-L917
            // $ echo -n eventum/eventum:docs/examples/workflow/class.example.php | md5sum
            'fc4afcb3e485b08772a0e9bddd861b01' => $baseDir . '/docs/examples/workflow/class.example.php',
        ];

        // add classmap
        $loader->addClassMap($classmap);

        // add namespaces (psr-0)
        foreach ($psr0 as $namespace => $path) {
            $loader->add($namespace, $path);
        }

        // add namespaces (psr-4)
        foreach ($psr4 as $namespace => $path) {
            $loader->addPsr4($namespace, $path);
        }

        // add files
        foreach ($files as $fileIdentifier => $file) {
            $loader->autoloadFile($fileIdentifier, $file);
        }
    }

    /**
     * Return list of workflow classes.
     *
     * @return string[]
     */
    public function getAvailableWorkflows(): array
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
    public function getAvailableCustomFields(): array
    {
        return [
            CustomField\CscNumberCustomField::class,
            CustomField\DefaultValueCustomField::class,
            CustomField\DynamicAjaxCustomField::class,
            CustomField\DynamicCustomField::class,
            CustomField\IsbnHcCustomField::class,
        ];
    }

    /**
     * Return list of partner classes.
     *
     * @return string[]
     */
    public function getAvailablePartners(): array
    {
        return [
            Partner\ExamplePartner::class,
        ];
    }

    /**
     * Return list of CRM classes.
     *
     * @return string[]
     */
    public function getAvailableCRMs(): array
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
    public function getSubscribers(): array
    {
        return [
            Subscriber\CommitSubscriber::class,
            Subscriber\CryptoSubscriber::class,
            Subscriber\GitlabLinkSubscriber::class,
            Subscriber\HistorySubscriber::class,
            Subscriber\UserSubscriber::class,
        ];
    }

    /**
     * Return class names implementing Auth\Adapter\AdapterInterface
     *
     * @return string[]
     * @see \Eventum\Extension\Provider\AuthAdapterProvider
     */
    public function getAvailableAuthAdapters(): array
    {
        return [
            NullAuthAdapter::class,
        ];
    }
}
