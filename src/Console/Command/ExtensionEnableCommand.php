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

use Eventum\Extension\Provider\ExtensionProvider;
use Eventum\Extension\RegisterExtension;
use InvalidArgumentException;
use LogicException;
use ReflectionException;
use Setup;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExtensionEnableCommand extends SymfonyCommand
{
    public const DEFAULT_COMMAND = 'extension:enable';
    public const USAGE = self::DEFAULT_COMMAND . ' [filename] [classname]';

    protected static $defaultName = 'eventum:' . self::DEFAULT_COMMAND;

    /** @var OutputInterface */
    private $output;

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED)
            ->addArgument('classname', InputArgument::REQUIRED);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('filename');
        $classname = $input->getArgument('classname');

        $this($output, $filename, $classname);

        return 0;
    }

    public function __invoke(OutputInterface $output, $filename, $classname): void
    {
        $this->output = $output;
        $this->setupExtension($filename, $classname);
    }

    /**
     * Setup Extension being loaded by default
     *
     * @param string $extensionFile path to filename that loads extension
     * @param string $extensionName class name of extension, must implement ExtensionProvider
     * @throws ReflectionException
     */
    public function setupExtension(?string $extensionFile, ?string $extensionName): void
    {
        $register = new RegisterExtension();
        $this->loadExtensionFile($extensionFile);

        if (!$extensionName) {
            throw new InvalidArgumentException('Extension class name not specified');
        }

        try {
            $register->register($extensionName);
        } catch (LogicException $e) {
            $this->output->writeln("<info>{$extensionName}</info>: {$e->getMessage()}");

            return;
        }

        $this->output->writeln("Enabled extension: <info>{$extensionName}</info>");
    }

    private function loadExtensionFile($fileName): void
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
}
