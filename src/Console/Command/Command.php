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

use Eventum\Console\ConsoleTrait;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command
{
    use ConsoleTrait;

    // compact constants for writeln
    protected const DEBUG = OutputInterface::VERBOSITY_DEBUG;
    protected const VERBOSE = OutputInterface::VERBOSITY_VERBOSE;
    protected const VERY_VERBOSE = OutputInterface::VERBOSITY_VERY_VERBOSE;
}
