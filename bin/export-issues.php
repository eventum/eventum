#!/usr/bin/php
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

require_once __DIR__ . '/../init.php';

use Eventum\Console\Application;
use Eventum\Console\Command\ExportIssuesCommand as Command;

$app = new Application();
$app->command(Command::USAGE, [new Command(), 'execute']);
$app->setDefaultCommand(Command::DEFAULT_COMMAND, true);
$app->run();
