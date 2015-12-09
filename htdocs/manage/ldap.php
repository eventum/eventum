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
$tpl->setTemplate('manage/ldap.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_REPORTER) {
    Misc::setMessage('Sorry, you are not allowed to access this page.', Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'update') {
    $setup['host'] = $_POST['host'];
    $setup['port'] = $_POST['port'];
    $setup['binddn'] = $_POST['binddn'];
    $setup['bindpw'] = $_POST['bindpw'];
    $setup['basedn'] = $_POST['basedn'];
    $setup['userdn'] = $_POST['userdn'];
    $setup['user_filter'] = $_POST['user_filter'];
    $setup['customer_id_attribute'] = $_POST['customer_id_attribute'];
    $setup['contact_id_attribute'] = $_POST['contact_id_attribute'];
    $setup['create_users'] = $_POST['create_users'];
    $setup['default_role'] = $_POST['default_role'];
    $res = Setup::save(array('ldap' => $setup));
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the setup information was saved successfully.', Misc::MSG_INFO),
            -1  =>  array("ERROR: The system doesn't have the appropriate permissions to create the configuration file
                            in the setup directory (" . APP_CONFIG_PATH . '). Please contact your local system
                            administrator and ask for write privileges on the provided path.', Misc::MSG_HTML_BOX),
            -2  =>  array("ERROR: The system doesn't have the appropriate permissions to update the configuration file
                            in the setup directory (" . APP_CONFIG_PATH . '/ldap.php). Please contact your local system
                            administrator and ask for write privileges on the provided filename.', Misc::MSG_HTML_BOX),
    ));

    $tpl->assign('result', $res);
}

$setup = Setup::setDefaults('ldap', LDAP_Auth_Backend::getDefaults());

$tpl->assign('setup', $setup);
$tpl->assign('project_list', Project::getAll());
$tpl->assign('project_roles', array(0 => 'No Access') + User::getRoles());
$tpl->assign('user_roles', User::getRoles(array('Customer')));

$tpl->displayTemplate();
