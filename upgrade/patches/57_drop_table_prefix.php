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
 * rename all tables to remove table prefix.
 */

use Eventum\Db;
use Eventum\Db\Adapter\AdapterInterface;

/** @var Closure $log */
/** @var AdapterInterface $db */

$setup = Setup::get()->database;
if (!$setup['table_prefix']) {
    // no table prefix. nothing to do
    return;
}

$tables = Db\Table::getTableList();

// check that tables can be renamed, no name conflict
$conflicts = [];
foreach ($tables as $table) {
    $res = $db->getOne('SHOW TABLES LIKE ?', [$table]);
    if ($res) {
        $conflicts[] = $table;
    }
}

if ($conflicts) {
    $tl = implode(', ', $conflicts);
    throw new LogicException("Can not rename, tables already exists: $tl");
}

// rename tables
foreach ($tables as $table) {
    $db->query("alter table {{%$table}} rename as `$table`");
}

// update config without table prefix
$setup['table_prefix'] = '';
Setup::save();

// there's no simple way to handle this without hacking in some "reconnect" mechanism to db adapter,
// so just tell the user to re-run the command
$log('If you see error below about eventum_version table does not exist, please retry the upgrade command');
