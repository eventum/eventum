<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("select_project.tpl.html");

// check if cookies are enabled, first of all
if (!Auth::hasCookieSupport(APP_COOKIE)) {
    Auth::redirect("index.php?err=11");
}

if (!Auth::hasValidCookie(APP_COOKIE)) {
    Auth::redirect("index.php?err=5");
}

if (@$_GET["err"] == '') {
    $cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
    if ($cookie["remember"] && $cookie['prj_id'] != false) {
        if (!empty($_GET["url"])) {
            Auth::redirect($_GET["url"]);
        } else {
            Auth::redirect("list.php");
        }
    }

    Language::setPreference();

    // check if the list of active projects consists of just
    // one project, and redirect the user to the main page of the
    // application on that case
    $assigned_projects = Project::getAssocList(Auth::getUserID());
    if (count($assigned_projects) == 1) {
        list($prj_id,) = each($assigned_projects);
        Auth::setCurrentProject($prj_id, 0);
        handleExpiredCustomer($prj_id);

        if (!empty($_GET["url"])) {
            Auth::redirect($_GET["url"]);
        } else {
            Auth::redirect("main.php");
        }
    } elseif ((!empty($_GET["url"])) && (
            (preg_match("/.*view\.php\?id=(\d*)/", $_GET["url"], $matches) > 0) ||
            (preg_match("/switch_prj_id=(\d*)/", $_GET["url"], $matches) > 0)
            )) {
        // check if url is directly linking to an issue, and if it is, don't prompt for project
        if (stristr($_GET["url"], 'view.php')) {
            $prj_id = Issue::getProjectID($matches[1]);
        } else {
            $prj_id = $matches[1];
        }
        if (!empty($assigned_projects[$prj_id])) {
            Auth::setCurrentProject($prj_id, 0);
            handleExpiredCustomer($prj_id);
            Auth::redirect($_GET["url"]);
        }
    }
}

if (@$_GET["err"] != '') {
    Auth::removeCookie(APP_PROJECT_COOKIE);
    $tpl->assign("err", $_GET["err"]);
}

$select_prj = (isset($_POST['cat']) && $_POST['cat'] == 'select') || (isset($_GET['project']) && $_GET['project']);
if ($select_prj) {
    $prj_id = (int )(@$_POST['cat'] == 'select') ? (int )@$_POST['project'] : (int )@$_GET['project'];
    $usr_id = Auth::getUserID();
    $projects = Project::getAssocList($usr_id);
    if (!in_array($prj_id, array_keys($projects))) {
        // show error message
        $tpl->assign("err", 1);
    } else {
        // create cookie and redirect
        if (empty($_POST["remember"])) {
            $_POST["remember"] = 0;
        }
        Auth::setCurrentProject($prj_id, $_POST["remember"]);
        handleExpiredCustomer($prj_id);

        if (!empty($_POST["url"])) {
            Auth::redirect($_POST["url"]);
        } else {
            Auth::redirect("list.php");
        }
    }
}
$tpl->displayTemplate();

function handleExpiredCustomer($prj_id)
{
    GLOBAL $tpl;

    if (Customer::hasCustomerIntegration($prj_id)) {
        // check if customer is expired
        $usr_id = Auth::getUserID();
        $contact_id = User::getCustomerContactID($usr_id);
        $customer_id = User::getCustomerID($usr_id);
        if ((!empty($contact_id)) && ($contact_id != -1)) {
            Customer::authenticateCustomer($prj_id, $customer_id, $contact_id);
        }
    }
}
