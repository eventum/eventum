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

// delay language init if we're saving language
if (!empty($_POST['language'])) {
    define('SKIP_LANGUAGE_INIT', true);
}
require_once __DIR__ . '/../init.php';

$controller = new Eventum\Controller\PreferencesController();
$controller->run();
