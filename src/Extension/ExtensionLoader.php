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
        // see if classname is provided
        if (class_exists($backend)) {
            return new $backend();
        }

        // legacy mode where filename is provided
        $filename = $this->findClassFilename($backend);
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("Filename: '$filename' does not exist for '$backend'");
        }

        /** @noinspection PhpIncludeInspection */
        require_once $filename;

        $classname = $this->getClassName($backend);

        return new $classname();
    }

    /**
     * Get Classname -> Filename of extensions found.
     *
     * NOTE: this method does require_once to each of the files.
     *
     * @return array
     */
    public function getClassList()
    {
        $list = $files = [];
        foreach ($this->paths as $path) {
            $files = array_merge($files, Misc::getFileList($path));
        }

        foreach ($files as $filename) {
            $basename = basename($filename);
            $classname = $this->getClassName($basename);

            if (!$this->isExtension($filename, $classname)) {
                continue;
            }

            if ($this->parent_class && !is_subclass_of($classname, $this->parent_class)) {
                continue;
            }

            // add alternative capitalization
            // some places use it inconsistently
            // can't use reflection here to figure out correct name
            $classname = ucwords(str_replace('_', ' ', $classname));
            $classname = str_replace(' ', '_', $classname);

            $list[$classname] = $filename;
        }

        return $list;
    }

    /**
     * Determines whether $className is an extension.
     * That is it is an class that can be instantiated.
     *
     * @param string $filename
     * @param string $classname
     * @return bool
     */
    private function isExtension($filename, $classname)
    {
        // skip if filename pattern gave no result
        if (!$classname) {
            return false;
        }

        // autoload, or load manually
        if (!class_exists($classname)) {
            require_once $filename;

            if (!class_exists($classname)) {
                // still not found. skip it
                return false;
            }
        }
        $rc = new ReflectionClass($classname);

        return $rc->isInstantiable();
    }

    /**
     * Get class name from file name.
     *
     * @param string $filename
     * @return string
     * @internal
     */
    public function getClassName($filename)
    {
        if (!preg_match('/^class\.(.*)\.php$/', $filename, $matches)) {
            return null;
        }

        return sprintf($this->classFormat, $matches[1]);
    }

    /**
     * Find class filename from set of directories
     *
     * @param string $filename
     * @return null|string
     * @internal
     */
    public function findClassFilename($filename)
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
