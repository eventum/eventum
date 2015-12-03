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

require_once __DIR__ . '/../../init.php';

// if there is no field ID, return false
if (empty($_GET['fld_id'])) {
    exit(0);
}

$backend = Custom_Field::getBackend($_GET['fld_id']);
if (is_object($backend) && is_subclass_of($backend, 'Dynamic_Custom_Field_Backend')) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($backend->getDynamicOptions($_GET));
}
