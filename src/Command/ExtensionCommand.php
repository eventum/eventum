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

namespace Eventum\Command;

use ReflectionClass;
use RuntimeException;
use Setup;

class ExtensionCommand extends Command
{
    protected function execute()
    {
        global $argc, $argv;

        if ($argc != 3) {
            throw new RuntimeException('Usage: filename classname');
        }

        $this->setupExtension($argv[1], $argv[2]);
    }

    /**
     * Setup Extension being loaded by default
     *
     * @param string $extensionFile path to filename that loads extension
     * @param string $extensionName class name of extension, must implement ExtensionInterface
     */
    public function setupExtension($extensionFile, $extensionName)
    {
        require_once $extensionFile;

        // this will validate class name is valid
        $reflectionClass = new ReflectionClass($extensionName);

        $setup = Setup::get();

        if (isset($setup['extensions'][$extensionName])) {
            // already enabled?
            echo "Extension already enabled: {$extensionName}\n";

            return;
        }

        echo "Enabling extension: {$extensionName}\n";
        $setup['extensions'][$extensionName] = $reflectionClass->getFileName();
        Setup::save();
    }
}
