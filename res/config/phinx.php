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
 * Configuration proxy for sphix
 *
 * @link http://docs.phinx.org/en/latest/commands.html#configuration-file-parameter
 */

$config = DB_Helper::getConfig();

// TODO: use "connection" => $pdo_instance once PEAR DB support is dropped
// http://docs.phinx.org/en/latest/commands.html#configuration-file-parameter

return [
    'paths' => [
        'migrations' => 'db/migrations'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_database' => $config['database'],
        $config['database'] => [
            'adapter' => 'mysql',
            'host' => $config['hostname'],
            'name' => $config['database'],
            'user' => $config['username'],
            'pass' => $config['password'],
            'port' => $config['port'],
            'unix_socket' => $config['socket'],
        ]
    ]
];
