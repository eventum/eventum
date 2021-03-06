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

use Eventum\Logger\LoggerTrait;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends SymfonyCommand
{
    use LoggerTrait;

    protected const DEBUG = OutputInterface::VERBOSITY_DEBUG;
    protected const VERBOSE = OutputInterface::VERBOSITY_VERBOSE;
    protected const VERY_VERBOSE = OutputInterface::VERBOSITY_VERY_VERBOSE;

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $this->input = $input;
        $this->logger = $this->getLogger();
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    protected function writeln($messages, $options = 0): void
    {
        $this->output->writeln($messages, $options);
    }
}
