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

use Mail_Queue;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MailQueueTruncateCommand extends SymfonyCommand
{
    public const DEFAULT_COMMAND = 'mail-queue:truncate';
    public const USAGE = self::DEFAULT_COMMAND . ' [-q|--quiet] [--interval=]';
    private const DEFAULT_INTERVAL = '1 month';

    protected static $defaultName = 'eventum:' . self::DEFAULT_COMMAND;

    protected function configure(): void
    {
        $this
            ->addOption('interval', null, InputOption::VALUE_REQUIRED)
            ->addOption('quiet', 'q', InputOption::VALUE_NONE);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $interval = $input->getOption('interval') ?: self::DEFAULT_INTERVAL;
        $quiet = $input->getOption('quiet');

        $this($output, $quiet, $interval);

        return 0;
    }

    public function __invoke(OutputInterface $output, $quiet, $interval): void
    {
        $interval = $interval ?: self::DEFAULT_INTERVAL;
        Mail_Queue::truncate($interval);

        if (!$quiet) {
            $message = ev_gettext('Mail queue truncated by %1$s.', $interval);
            $output->writeln("<info>$message</info>");
        }
    }
}
