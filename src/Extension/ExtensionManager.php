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

use Composer\Autoload\ClassLoader;
use Eventum\Extension\Provider\AutoloadProvider;
use Eventum\Extension\Provider\CrmProvider;
use Eventum\Extension\Provider\CustomFieldProvider;
use Eventum\Extension\Provider\ExtensionProvider;
use Eventum\Extension\Provider\FactoryProvider;
use Eventum\Extension\Provider\PartnerProvider;
use Eventum\Extension\Provider\SubscriberProvider;
use Eventum\Extension\Provider\WorkflowProvider;
use Eventum\Monolog\Logger;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use Setup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use Zend\Config\Config;

class ExtensionManager
{
    /** @var Provider\ExtensionProvider[] */
    protected $extensions;

    /**
     * Singleton Extension Manager
     *
     * @return ExtensionManager
     */
    public static function getManager(): self
    {
        static $manager;
        if (!$manager) {
            $manager = new self();
        }

        return $manager;
    }

    public function __construct()
    {
        $this->extensions = $this->loadExtensions();
    }

    /**
     * Return instances of Workflow implementations.
     */
    public function getWorkflowClasses(): Generator
    {
        return $this->createInstances('getAvailableWorkflows', function (ExtensionProvider $extension) {
            return $extension instanceof WorkflowProvider;
        });
    }

    /**
     * Return instances of Custom Field implementations.
     */
    public function getCustomFieldClasses(): Generator
    {
        return $this->createInstances('getAvailableCustomFields', function (ExtensionProvider $extension) {
            return $extension instanceof CustomFieldProvider;
        });
    }

    /**
     * Return instances of CRM implementations.
     */
    public function getCustomerClasses(): Generator
    {
        return $this->createInstances('getAvailableCRMs', function (ExtensionProvider $extension) {
            return $extension instanceof CrmProvider;
        });
    }

    /**
     * Return instances of Partner implementations.
     */
    public function getPartnerClasses(): Generator
    {
        return $this->createInstances('getAvailablePartners', function (ExtensionProvider $extension) {
            return $extension instanceof PartnerProvider;
        });
    }

    /**
     * Get classes implementing EventSubscriberInterface.
     *
     * @see http://symfony.com/doc/current/components/event_dispatcher.html#using-event-subscribers
     */
    public function getSubscribers(): Generator
    {
        return $this->createInstances(__FUNCTION__, function (ExtensionProvider $extension) {
            return $extension instanceof SubscriberProvider;
        });
    }

    /**
     * Create instances of classes returned from each extension $methodName.
     */
    protected function createInstances(string $methodName, callable $filter): Generator
    {
        foreach ($this->extensions as $extension) {
            if (!$filter($extension)) {
                continue;
            }
            foreach ($extension->$methodName() as $className) {
                try {
                    yield $className => $this->createInstance($extension, $className);
                } catch (Throwable $e) {
                    Logger::app()->error("Unable to create $className: {$e->getMessage()}", ['exception' => $e]);
                }
            }
        }
    }

    /**
     * Create new instance of named class,
     * use factory from extensions that provide factory method.
     *
     * @return object
     */
    protected function createInstance(Provider\ExtensionProvider $preferredExtension, string $className)
    {
        $getSortedExtensions = static function (array $extensions) use ($preferredExtension): Generator {
            // prefer provided extension
            if ($preferredExtension instanceof FactoryProvider) {
                yield $preferredExtension;
            }
            unset($extensions[get_class($preferredExtension)]);

            foreach ($extensions as $extension) {
                if ($extension instanceof FactoryProvider) {
                    yield $extension;
                }
            }
        };

        foreach ($getSortedExtensions($this->extensions) as $extension) {
            /** @var FactoryProvider $extension */
            $object = $extension->factory($className);

            // extension may not provide factory for this class
            // try next extension
            if ($object) {
                return $object;
            }
        }

        // fall back to autoloading
        if (!class_exists($className)) {
            throw new InvalidArgumentException("Class '$className' does not exist");
        }

        return new $className();
    }

    /**
     * Create all extensions, initialize autoloader on them.
     *
     * @return Provider\ExtensionProvider[]
     */
    protected function loadExtensions(): array
    {
        $extensions = [];
        $loader = $this->getAutoloader();

        foreach ($this->getExtensionFiles() as $classname => $filename) {
            try {
                $extension = $this->loadExtension($classname, $filename);
            } catch (Throwable $e) {
                Logger::app()->error("Unable to load $classname: {$e->getMessage()}", ['exception' => $e]);
                continue;
            }

            if ($extension instanceof AutoloadProvider) {
                $extension->registerAutoloader($loader);
            }
            $extensions[$classname] = $extension;
        }

        return $extensions;
    }

    /**
     * Load $filename and create $classname instance
     */
    protected function loadExtension(string $classname, string $filename): Provider\ExtensionProvider
    {
        // class may already be loaded
        // can ignore the filename requirement
        if (!class_exists($classname)) {
            if (!file_exists($filename)) {
                throw new InvalidArgumentException("File does not exist: $filename");
            }

            /** @noinspection PhpIncludeInspection */
            require_once $filename;

            if (!class_exists($classname)) {
                throw new InvalidArgumentException("Could not load $classname from $filename");
            }
        }

        return new $classname();
    }

    /**
     * Get configured extensions from setup.php
     *
     * @return Config|\Traversable|array
     */
    protected function getExtensionFiles()
    {
        return Setup::get()['extensions'] ?: [];
    }

    /**
     * Return composer autoloader
     *
     * @return ClassLoader
     */
    protected function getAutoloader(): ClassLoader
    {
        $baseDir = dirname(__DIR__, 2);
        foreach ([$baseDir . '/vendor/autoload.php', $baseDir . '/../../../vendor/autoload.php'] as $autoload) {
            if (file_exists($autoload)) {
                break;
            }
        }

        if (!isset($autoload)) {
            throw new RuntimeException('Could not locate autoloader');
        }

        return require $autoload;
    }
}
