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
use Setup;
use Symfony\Component\Console\Output\OutputInterface;

class ExtensionCommand
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
     */
    public function setupExtension($extensionFile, $extensionName)
    {
        require_once $extensionFile;

        // this will validate class name is valid
        $reflectionClass = new ReflectionClass($extensionName);

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
}
