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

use Symfony\Component\Console\Output\OutputInterface;

abstract class Command
{
    // compact constants for writeln
    const DEBUG = OutputInterface::VERBOSITY_DEBUG;
    const VERBOSE = OutputInterface::VERBOSITY_VERBOSE;
    const VERY_VERBOSE = OutputInterface::VERBOSITY_VERY_VERBOSE;

    /** @var OutputInterface */
    protected $output;

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    protected function writeln($messages, $options = 0)
    {
        $this->output->writeln($messages, $options);
    }
}
