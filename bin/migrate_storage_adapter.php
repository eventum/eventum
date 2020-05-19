#!/usr/bin/env php
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

/**
 * A script that changes migrates attachments from one storage backend to another another storage backend.
 *
 * This may take a very long time to run, depending on how much data needs to be migrated.
 *
 * WARNING: Migrating data is a risky business. Make sure you have EVERYTHING backed up before you begin this process.
 */

use Eventum\Console\Application;
use Eventum\Console\Command\AttachmentMigrateCommand as Command;

require_once __DIR__ . '/../init.php';

$app = new Application();
$app->command(Command::USAGE, Command::class);
$app->setDefaultCommand(Command::DEFAULT_COMMAND, true);
$app->run();
