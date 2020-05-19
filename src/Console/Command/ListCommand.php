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

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends \Symfony\Component\Console\Command\ListCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all commands not just Eventum builtin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('all')) {
            $this->hideCommands();
        }

        return parent::execute($input, $output);
    }

    /**
     * Hide commands from bundle, as they are not usable.
     *
     * @see https://symfony.com/doc/current/console/hide_commands.html
     */
    private function hideCommands(): void
    {
        $hiddenNamespaces = [
            'assets',
            'cache',
            'config',
            'debug',
            'doctrine',
            'lint',
            'router',
            'secrets',
            'security',
        ];

        /** @var Application $app */
        $app = $this->getApplication();
        $commands = $app->all();

        foreach ($commands as $commandName => $command) {
            $namespace = $app->extractNamespace($commandName, 1);
            if (!in_array($namespace, $hiddenNamespaces, 1)) {
                continue;
            }
            $command->setHidden(true);
        }
    }
}
