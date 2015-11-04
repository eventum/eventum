<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2010 Bryan Alsdorf                                     |
// | Copyright (c) 2015 Eventum Team.                                     |
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

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('manage/products.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage('Sorry, you are not allowed to access this page.', Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}
if (@$_POST['cat'] == 'new') {
    $res = Product::insert($_POST['title'], $_POST['version_howto'], $_POST['rank'], @$_POST['removed'],
        @$_POST['email']);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the product was added successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to add the product.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Product::update($_POST['id'], $_POST['title'], $_POST['version_howto'], $_POST['rank'], @$_POST['removed'],
        @$_POST['email']);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the product was updated successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to update the product.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Product::remove($_POST['items']);
}

if (@$_GET['cat'] == 'edit') {
    $info = Product::getDetails($_GET['id']);
    $tpl->assign('info', $info);
    $user_options = User::getActiveAssocList(Auth::getCurrentProject(), User::ROLE_CUSTOMER, false, $_GET['id']);
} else {
    $user_options = User::getActiveAssocList(Auth::getCurrentProject(), User::ROLE_CUSTOMER, true);
}

$tpl->assign('list', Product::getList());
$tpl->assign('project_list', Project::getAll());

$tpl->displayTemplate();
