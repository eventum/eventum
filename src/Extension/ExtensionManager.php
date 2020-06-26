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

use ArrayIterator;
use Eventum\Config\Config;
use Eventum\Logger\LoggerTrait;
use Eventum\ServiceContainer;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Throwable;

class ExtensionManager implements Provider\RouteProvider
{
    use LoggerTrait;

    /** @var Provider\ExtensionProvider[] */
    protected $extensions;

    /**
     * Singleton Extension Manager
     *
     * @return ExtensionManager
     * @deprecated since 3.8.11, use ServiceContainer::get(ExtensionManager::class) instead
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
        return $this->createInstances('getAvailableWorkflows', static function (Provider\ExtensionProvider $extension) {
            return $extension instanceof Provider\WorkflowProvider;
        });
    }

    /**
     * Return instances of Custom Field implementations.
     */
    public function getCustomFieldClasses(): Generator
    {
        return $this->createInstances('getAvailableCustomFields', static function (Provider\ExtensionProvider $extension) {
            return $extension instanceof Provider\CustomFieldProvider;
        });
    }

    /**
     * Return instances of CRM implementations.
     */
    public function getCustomerClasses(): Generator
    {
        return $this->createInstances('getAvailableCRMs', static function (Provider\ExtensionProvider $extension) {
            return $extension instanceof Provider\CrmProvider;
        });
    }

    /**
     * Return instances of Partner implementations.
     */
    public function getPartnerClasses(): Generator
    {
        return $this->createInstances('getAvailablePartners', static function (Provider\ExtensionProvider $extension) {
            return $extension instanceof Provider\PartnerProvider;
        });
    }

    /**
     * Get classes implementing EventSubscriberInterface.
     *
     * @see http://symfony.com/doc/current/components/event_dispatcher.html#using-event-subscribers
     */
    public function getSubscribers(): Generator
    {
        return $this->createInstances(__FUNCTION__, static function (Provider\ExtensionProvider $extension) {
            return $extension instanceof Provider\SubscriberProvider;
        });
    }

    /**
     * Return instances of Partner implementations.
     */
    public function getAvailableAuthAdapters(): iterable
    {
        /** @var Provider\RouteProvider[] $extensions */
        $extensions = $this->filterExtensions(static function (Provider\ExtensionProvider $extension) {
            return $extension instanceof Provider\AuthAdapterProvider;
        });

        foreach ($extensions as $extension) {
            yield from new ArrayIterator($extension->getAvailableAuthAdapters());
        }
    }

    public function configureRoutes(RouteCollectionBuilder $routes): void
    {
        /** @var Provider\RouteProvider[] $extensions */
        $extensions = $this->filterExtensions(static function (Provider\ExtensionProvider $extension) {
            return $extension instanceof Provider\RouteProvider;
        });

        foreach ($extensions as $extension) {
            $extension->configureRoutes($routes);
        }
    }

    private function filterExtensions(callable $filter): Generator
    {
        foreach ($this->extensions as $extension) {
            if (!$filter($extension)) {
                continue;
            }
            yield $extension;
        }
    }

    /**
     * Create instances of classes returned from each extension $methodName.
     */
    private function createInstances(string $methodName, callable $filter): Generator
    {
        foreach ($this->extensions as $extension) {
            if (!$filter($extension)) {
                continue;
            }
            foreach ($extension->$methodName() as $className) {
                try {
                    yield $className => $this->createInstance($extension, $className);
                } catch (Throwable $e) {
                    $this->error("Unable to create $className: {$e->getMessage()}", ['exception' => $e]);
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
            if ($preferredExtension instanceof Provider\FactoryProvider) {
                yield $preferredExtension;
            }
            unset($extensions[get_class($preferredExtension)]);

            foreach ($extensions as $extension) {
                if ($extension instanceof Provider\FactoryProvider) {
                    yield $extension;
                }
            }
        };

        foreach ($getSortedExtensions($this->extensions) as $extension) {
            /** @var Provider\FactoryProvider $extension */
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
        $container = ServiceContainer::getInstance();

        foreach ($this->getExtensionFiles() as $classname => $filename) {
            try {
                $extension = $this->loadExtension($classname, $filename);
            } catch (Throwable $e) {
                $this->error("Unable to load $classname: {$e->getMessage()}", ['exception' => $e]);
                continue;
            }

            if ($extension instanceof Provider\AutoloadProvider) {
                $extension->registerAutoloader($loader);
            }
            if ($extension instanceof Provider\ServiceProvider) {
                $extension->register($container);
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
        return ServiceContainer::getConfig()['extensions'] ?: [];
    }

    /**
     * Return Composer autoloader decorated with Eventum ClassLoader
     *
     * @return ClassLoader
     */
    protected function getAutoloader(): ClassLoader
    {
        $baseDir = dirname(__DIR__, 2);
        $searchPaths = [
            $baseDir . '/vendor/autoload.php',
            $baseDir . '/../../../vendor/autoload.php',
        ];
        foreach ($searchPaths as $autoload) {
            if (file_exists($autoload)) {
                break;
            }
        }

        if (!isset($autoload)) {
            throw new RuntimeException('Could not locate autoloader');
        }

        /** @noinspection PhpIncludeInspection */
        $loader = require $autoload;

        return new ClassLoader($loader);
    }
}
