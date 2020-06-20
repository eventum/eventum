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

use Eventum\Config\Config;
use Eventum\Extension\Provider\ExtensionProvider;
use Eventum\ServiceContainer;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionException;
use Setup;

class RegisterExtension
{
    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = ServiceContainer::getConfig()['extensions'];
    }

    public function enable(string $className, bool $enable = true): void
    {
        $extension = $this->getExtensionClass($className);

        if ($enable && !$this->hasExtension($extension)) {
            $this->register($extension->getName());
        }

        if (!$enable) {
            $this->unregister($extension->getName());
        }
    }

    /**
     * @throws LogicException
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function register(...$extensions): void
    {
        foreach ($extensions as $className) {
            $extension = $this->getExtensionClass($className);
            if ($this->hasExtension($extension)) {
                throw new LogicException('Extension already enabled');
            }
            $this->config[$extension->getName()] = $extension->getFileName();
        }

        Setup::save();
    }

    public function unregister(...$extensions): void
    {
        foreach ($extensions as $className) {
            $extension = $this->getExtensionClass($className);
            unset($this->config[$extension->getName()]);
        }

        Setup::save();
    }

    private function hasExtension(ReflectionClass $extension): bool
    {
        return isset($this->config[$extension->getName()]);
    }

    /**
     * Return ReflectionClass, validate that $extensionName is valid Extension.
     *
     * @param string $extensionName
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @return ReflectionClass
     */
    private function getExtensionClass(string $extensionName): ReflectionClass
    {
        $reflectionClass = new ReflectionClass($extensionName);

        $implements = $reflectionClass->implementsInterface(ExtensionProvider::class);
        if (!$implements) {
            throw new InvalidArgumentException("Class $extensionName does not implement ExtensionProvider");
        }

        return $reflectionClass;
    }
}
