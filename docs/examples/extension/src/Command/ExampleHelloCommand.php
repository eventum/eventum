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

namespace Example\Command;

use Eventum\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExampleHelloCommand extends BaseCommand
{
    public const DEFAULT_COMMAND = 'example:hello';

    protected static $defaultName = 'eventum:' . self::DEFAULT_COMMAND;

    protected function configure(): void
    {
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->sayHello();

        return 0;
    }

    private function sayHello(): void
    {
        $this->writeln('<info>Hello world!</info>');
    }
}
