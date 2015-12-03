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
 * This file is used to call various customer pages that are outside of the web
 * root.
 */
require_once '../../init.php';

$prj_id = Auth::getCurrentProject();
$page = $_REQUEST['page'];

if (!CRM::hasCustomerIntegration($prj_id)) {
    echo 'No customer integration for specified project.';
    exit;
}
$crm = CRM::getInstance($prj_id);
require $crm->getHtdocsPath() . basename($page) . '.php';
