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

/*
 * ensure we have db adapter class name present in config
 */

use Eventum\Db\Adapter\AdapterInterface;

/** @var Closure $log */
/** @var AdapterInterface $db */

$setup = Setup::get()->database;
if ($setup['classname']) {
    // classname already set
    return;
}

$setup['classname'] = 'Pear';
Setup::save();
