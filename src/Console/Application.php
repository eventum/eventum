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

namespace Eventum\Console;

use Eventum\EventDispatcher\EventManager;
use Eventum\ServiceContainer;
use Silly\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Application extends BaseApplication
{
    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);
        $this->setDispatcher(EventManager::getEventDispatcher());
        $this->useContainer(ServiceContainer::getContainer());
    }

    public function routeDeprecatedCommand(array $argv): int
    {
        $input = new ArgvInput($argv);
        $output = new ConsoleOutput();
        $quiet = $input->hasParameterOption(['--quiet', '-q'], true);

        if (!$quiet) {
            $commandLine = implode(' ', array_slice($argv, 1));
            $output->writeln("<error>{$argv[0]}</error> is deprecated, invoking <info>bin/console.php $commandLine</info>");
        }

        $app = ServiceContainer::getApplication();

        return $app->run($input, $output);
    }
}
