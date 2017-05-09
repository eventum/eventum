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
use Misc;
use ReflectionClass;

class ExtensionLoader
{
    /** @var array */
    private $paths;

    /** @var string */
    private $classFormat;

    /** @var string */
    private $parent_class;

    /**
     * ExtensionLoader constructor.
     *
     * @param array|string $paths
     * @param string $classFormat format for creating class from filename
     * @param string $parent_class Only include classes that are a subclass of this
     */
    public function __construct($paths, $classFormat = null, $parent_class = null)
    {
        $this->paths = is_string($paths) ? [$paths] : $paths;
        $this->classFormat = $classFormat;
        $this->parent_class = $parent_class;
    }

    /**
     * Create instance of named backend
     *
     * @param string $backend
     * @return object
     */
    public function createInstance($backend)
    {
        $filename = $this->getClassFilename($backend);
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("Filename: $filename does not exist");
        }

        /** @noinspection PhpIncludeInspection */
        require_once $filename;

        $classname = $this->getClassName($backend);

        return new $classname();
    }

    /**
     * Get Filename -> Classname of extensions found
     *
     * @return array
     */
    public function getFileList()
    {
        $list = $files = [];
        foreach ($this->paths as $path) {
            $files = array_merge($files, Misc::getFileList($path));
        }

        foreach ($files as $file) {
            $fileName = basename($file);
            $className = $this->getClassName($fileName);

            if (!$this->isExtension($file, $className)) {
                continue;
            }

            if ($this->parent_class && !is_subclass_of($className, $this->parent_class)) {
                continue;
            }

            $list[$fileName] = $this->getDisplayName($className);
        }

        return $list;
    }

    /**
     * Determines whether $className is an extension.
     * That is it is an class that can be instantiated.
     *
     * @param string $filename
     * @param string $className
     * @return bool
     */
    private function isExtension($filename, $className)
    {
        // skip if filename pattern gave no result
        if (!$className) {
            return false;
        }

        // autoload, or load manually
        if (!class_exists($className)) {
            require_once $filename;

            if (!class_exists($className)) {
                // still not found. skip it
                return false;
            }
        }
        $rc = new ReflectionClass($className);

        return $rc->isInstantiable();
    }

    /**
     * Get class name from file name.
     *
     * @param string $fileName
     * @return string
     */
    private function getClassName($fileName)
    {
        if (!preg_match('/^class\.(.*)\.php$/', $fileName, $matches)) {
            return null;
        }

        return sprintf($this->classFormat, $matches[1]);
    }

    /**
     * Returns the 'pretty' name of the backend
     *
     * @param string $fileName
     * @return string
     */
    private function getDisplayName($fileName)
    {
        return ucwords(str_replace('_', ' ', $fileName));
    }

    /**
     * Find class filename from set of directories
     *
     * @param string $filename
     * @return null|string
     */
    private function getClassFilename($filename)
    {
        foreach ($this->paths as $path) {
            $class_filename = "$path/$filename";

            if (!file_exists($class_filename)) {
                continue;
            }

            return $class_filename;
        }

        return null;
    }
}
