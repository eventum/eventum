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

namespace Eventum\Console\Command;

use Eventum\Extension\ExtensionInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Setup;
use Symfony\Component\Console\Output\OutputInterface;

class ExtensionEnableCommand
{
    const DEFAULT_COMMAND = 'extension:enable';
    const USAGE = self::DEFAULT_COMMAND . ' [filename] [classname]';

    /** @var OutputInterface */
    private $output;

    public function execute(OutputInterface $output, $filename, $classname)
    {
        $this->output = $output;

        $this->setupExtension($filename, $classname);
    }

    /**
     * Setup Extension being loaded by default
     *
     * @param string $extensionFile path to filename that loads extension
     * @param string $extensionName class name of extension, must implement ExtensionInterface
     * @throws ReflectionException
     */
    public function setupExtension($extensionFile, $extensionName)
    {
        $this->loadExtensionFile($extensionFile);
        $reflectionClass = $this->getExtensionClass($extensionName);

        $setup = Setup::get();

        if (isset($setup['extensions'][$extensionName])) {
            // already enabled?
            $this->output->writeln("Extension already enabled: <info>{$extensionName}</info>");

            return;
        }

        $this->output->writeln("Enabling extension: <info>{$extensionName}</info>");
        $setup['extensions'][$extensionName] = $reflectionClass->getFileName();
        Setup::save();
    }

    private function loadExtensionFile($fileName)
    {
        if (!$fileName) {
            throw new InvalidArgumentException('Extension filename not specified');
        }

        if (!is_file($fileName)) {
            throw new InvalidArgumentException("$fileName is not regular file");
        }

        /** @noinspection PhpIncludeInspection */
        require_once $fileName;
    }

    /**
     * Return ReflectionClass, validate that $extensionName is valid Extension.
     *
     * @param string $extensionName
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @return ReflectionClass
     */
    private function getExtensionClass($extensionName)
    {
        if (!$extensionName) {
            throw new InvalidArgumentException('Extension class name not specified');
        }

        $reflectionClass = new ReflectionClass($extensionName);

        $implements = $reflectionClass->implementsInterface(ExtensionInterface::class);
        if (!$implements) {
            throw new InvalidArgumentException("Class $extensionName does not implement ExtensionInterface");
        }

        return $reflectionClass;
    }
}
