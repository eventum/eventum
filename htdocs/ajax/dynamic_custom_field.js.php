<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2005-2015 Eventum Team.                                |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
//

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
