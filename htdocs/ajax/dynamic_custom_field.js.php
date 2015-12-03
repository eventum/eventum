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

Auth::checkAuthentication();

if (!empty($_REQUEST['iss_id'])) {
    $fields = Custom_Field::getListByIssue(Auth::getCurrentProject(), $_REQUEST['iss_id']);
} else {
    $fields = Custom_Field::getListByProject(Auth::getCurrentProject(), $_REQUEST['form_type']);
}
$data = array();
foreach ($fields as $field) {
    $backend = Custom_Field::getBackend($field['fld_id']);
    if ((is_object($backend)) && (is_subclass_of($backend, 'Dynamic_Custom_Field_Backend'))) {
        $field['structured_data'] = $backend->getStructuredData();
        $data[] = $field;
    }
}

header('Content-Type: text/javascript; charset=UTF-8');
$tpl = new Template_Helper();
$tpl->setTemplate('js/dynamic_custom_field.tpl.js');
$tpl->assign('fields', $data);
$tpl->displayTemplate();
