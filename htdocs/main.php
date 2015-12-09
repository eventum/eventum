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

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('main.tpl.html');

Auth::checkAuthentication();

$prj_id = Auth::getCurrentProject();
$role_id = Auth::getCurrentRole();
$usr_id = Auth::getUserID();

// redirect partners to list.php instead of sanitizing this page
if (User::isPartner($usr_id)) {
    Auth::redirect('list.php');
}

if (isset($_REQUEST['hide_closed'])) {
    Auth::setCookie(APP_HIDE_CLOSED_STATS_COOKIE, $_REQUEST['hide_closed'], time() + Date_Helper::YEAR);
    $_COOKIE[APP_HIDE_CLOSED_STATS_COOKIE] = $_REQUEST['hide_closed'];
}
if (isset($_COOKIE[APP_HIDE_CLOSED_STATS_COOKIE])) {
    $hide_closed = $_COOKIE[APP_HIDE_CLOSED_STATS_COOKIE];
} else {
    $hide_closed = 0;
}
$tpl->assign('hide_closed', $hide_closed);

if ($role_id == User::ROLE_CUSTOMER) {
    $crm = CRM::getInstance($prj_id);
    // need the activity dashboard here
    $contact_id = User::getCustomerContactID($usr_id);
    $customer_id = Auth::getCurrentCustomerID();
    $tpl->assign(array(
        'contact'   =>  $crm->getContact($contact_id),
        'customer'  =>  $crm->getCustomer($customer_id),
    ));
} else {
    if ((Auth::getCurrentRole() <= User::ROLE_REPORTER) && (Project::getSegregateReporters($prj_id))) {
        $tpl->assign('hide_stats', true);
    } else {
        $tpl->assign('hide_stats', false);
        $tpl->assign('status', Stats::getStatus());
        $tpl->assign('releases', Stats::getRelease($hide_closed));
        $tpl->assign('categories', Stats::getCategory($hide_closed));
        $tpl->assign('priorities', Stats::getPriority($hide_closed));
        $tpl->assign('users', Stats::getUser($hide_closed));
        $tpl->assign('emails', Stats::getEmailStatus($hide_closed));
        $tpl->assign('pie_chart', Stats::getPieChart($hide_closed));
    }
}

if (@$_REQUEST['hide_closed'] == '') {
    $Stats_Search_Profile = Search_Profile::getProfile($usr_id, $prj_id, 'stats');

    if (!empty($Stats_Search_Profile)) {
        $tpl->assign('hide_closed', $Stats_Search_Profile['hide_closed']);
    }
} else {
    $tpl->assign('hide_closed', @$_REQUEST['hide_closed']);
    Search_Profile::save($usr_id, $prj_id, 'stats', array('hide_closed' => @$_REQUEST['hide_closed']));
}

$tpl->assign('news', News::getListByProject($prj_id));

$tpl->displayTemplate();
