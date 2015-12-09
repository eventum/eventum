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
$tpl->setTemplate('select_project.tpl.html');

session_start();

// check if cookies are enabled, first of all
if (!AuthCookie::hasCookieSupport()) {
    Auth::redirect('index.php?err=11');
}

if (!AuthCookie::hasAuthCookie()) {
    Auth::redirect('index.php?err=5');
}

if (@$_GET['err'] == '') {
    $cookie = AuthCookie::getProjectCookie();
    if ($cookie['remember'] && $cookie['prj_id']) {
        if (!empty($_GET['url'])) {
            Auth::redirect($_GET['url']);
        } else {
            Auth::redirect('list.php');
        }
    }

    Language::setPreference();

    // check if the list of active projects consists of just
    // one project, and redirect the user to the main page of the
    // application on that case
    $assigned_projects = Project::getAssocList(Auth::getUserID());
    if (count($assigned_projects) == 1) {
        list($prj_id) = each($assigned_projects);
        AuthCookie::setProjectCookie($prj_id);
        checkCustomerAuthentication($prj_id);

        if (!empty($_GET['url'])) {
            Auth::redirect($_GET['url']);
        } else {
            Auth::redirect('list.php');
        }
    } elseif ((!empty($_GET['url'])) && (
            (preg_match("/.*view\.php\?id=(\d*)/", $_GET['url'], $matches) > 0) ||
            (preg_match("/switch_prj_id=(\d*)/", $_GET['url'], $matches) > 0)
            )) {
        // check if url is directly linking to an issue, and if it is, don't prompt for project
        if (stristr($_GET['url'], 'view.php')) {
            $prj_id = Issue::getProjectID($matches[1]);
        } else {
            $prj_id = $matches[1];
        }
        if (!empty($assigned_projects[$prj_id])) {
            AuthCookie::setProjectCookie($prj_id);
            checkCustomerAuthentication($prj_id);
            Auth::redirect($_GET['url']);
        }
    }
    $tpl->assign('active_projects', $assigned_projects);
}

if (@$_GET['err'] != '') {
    AuthCookie::removeProjectCookie();
    $tpl->assign('err', $_GET['err']);
}

$select_prj = (isset($_POST['cat']) && $_POST['cat'] == 'select') || (isset($_GET['project']) && $_GET['project']);
if ($select_prj) {
    $prj_id = (int) (@$_POST['cat'] == 'select') ? (int) @$_POST['project'] : (int) @$_GET['project'];
    $usr_id = Auth::getUserID();
    $projects = Project::getAssocList($usr_id);
    if (!in_array($prj_id, array_keys($projects))) {
        // show error message
        $tpl->assign('err', 1);
    } else {
        // create cookie and redirect
        if (empty($_POST['remember'])) {
            $_POST['remember'] = 0;
        }
        AuthCookie::setProjectCookie($prj_id, $_POST['remember']);
        checkCustomerAuthentication($prj_id);

        if (!empty($_POST['url'])) {
            Auth::redirect($_POST['url']);
        } else {
            Auth::redirect('list.php');
        }
    }
}
$tpl->displayTemplate();

function checkCustomerAuthentication($prj_id)
{
    if (CRM::hasCustomerIntegration($prj_id)) {
        $crm = CRM::getInstance($prj_id);
        // check if customer is expired
        $usr_id = Auth::getUserID();
        $contact_id = User::getCustomerContactID($usr_id);
        if (User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER) {
            $crm->authenticateCustomer();
        }
    }
}
