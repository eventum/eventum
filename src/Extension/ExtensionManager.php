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

use InvalidArgumentException;
use Setup;
use Zend\Config\Config;

class ExtensionManager
{
    /** @var ExtensionInterface[] */
    protected $extensions;

    /**
     * Singleton Extension Manager
     *
     * @return ExtensionManager
     */
    public static function getManager()
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
     * Create all extensions, initialize them
     *
     * @return ExtensionInterface[]
     */
    protected function loadExtensions()
    {
        $extensions = [];
        $loader = $this->getAutoloader();

        foreach ($this->getExtensionFiles() as $classname => $filename) {
            $extension = $this->loadExtension($classname, $filename);
            $extension->registerAutoloader($loader);
            $extensions[$classname] = $extension;
        }

        return $extensions;
    }

    /**
     * Load $filename and create $classname instance
     *
     * @param string $classname
     * @param string $filename
     * @return ExtensionInterface
     */
    protected function loadExtension($classname, $filename)
    {
        /** @noinspection PhpIncludeInspection */
        require_once $filename;

        if (!class_exists($classname)) {
            throw new InvalidArgumentException("Could not load $classname from $filename");
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
        return Setup::get()['extensions'];
    }

    /**
     * Return composer autoloader
     *
     * @return \Composer\Autoload\ClassLoader
     */
    protected function getAutoloader()
    {
        return require APP_PATH . '/vendor/autoload.php';
    }
}
