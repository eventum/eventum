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

use Composer\Autoload\ClassLoader as ComposerClassLoader;
use function composerRequireEventumCore;

/**
 * A proxy class to have stable interface and method to include files.
 *
 * @method addClassMap(array $classmap)
 * @method add(string $prefix, array | string $paths, bool $prepend = false)
 * @method addPsr4(string $prefix, array | string $paths, bool $prepend = false)
 */
class ClassLoader
{
    /** @var ComposerClassLoader */
    private $loader;

    public function __construct(ComposerClassLoader $loader)
    {
        $this->loader = $loader;
    }

    public function autoloadFile(string $fileIdentifier, string $file): void
    {
        /**
         * The name became fixed due "config.autoloader-suffix" in composer.json
         */
        composerRequireEventumCore($fileIdentifier, $file);
    }

    public function __call(string $name, array $arguments = [])
    {
        return $this->loader->$name(...$arguments);
    }
}
