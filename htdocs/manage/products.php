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
