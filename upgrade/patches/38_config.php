<?php
/*
 * Migrate config to new schema
 */

$setup = Setup::load();

// scm config
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

// note: db config migration can't be done due simple chicken-egg problem ;)
