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
 * Run phing upgrade and clear clear Symfony cache.
 */

use Eventum\ServiceContainer;
use Symfony\Component\Console\Input\StringInput;

require_once __DIR__ . '/../autoload.php';

function msg(string $msg): void
{
    echo "\n* $msg\n";
}

chdir(__DIR__ . '/..');

if (Setup::needsSetup()) {
    msg('Skipping Phinx migrate, as setup not complete.');
} else {
    msg('Running Phinx migrate...');
    $phinx = new Phinx\Console\PhinxApplication();
    $phinx->setDefaultCommand('migrate');
    $phinx->setAutoExit(false);
    $phinx->run();
}

msg('Running "cache:clear". This may take a while...');

$app = ServiceContainer::getApplication();
$app->run(new StringInput('cache:clear'));
