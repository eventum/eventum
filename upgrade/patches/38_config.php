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
 * Migrate config to new schema
 */

$setup = Setup::get();

// NOTE: db config migration can't be done due simple chicken-egg problem ;)

// 1. scm config
// handle legacy setup, convert existing config to be known under name 'default'
if (!isset($setup['scm'])) {
    $scm = array(
        'name' => 'default',
        'checkout_url' => $setup['checkout_url'],
        'diff_url' => $setup['diff_url'],
        'log_url' => $setup['scm_log_url'],
    );
    Setup::save(array('scm' => array($scm['name'] => $scm)));
}

// 2. fix smtp.auth boolean cast
$setup['smtp']['auth'] = (bool) $setup['smtp']['auth'];

// save it back
Setup::save();
