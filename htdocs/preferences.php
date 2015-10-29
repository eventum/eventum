<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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

// delay language init if we're saving language
if (!empty($_POST['language'])) {
    define('SKIP_LANGUAGE_INIT', true);
}
require_once __DIR__ . '/../init.php';

$cat = isset($_POST['cat']) ? (string) $_POST['cat'] : null;
$usr_id = Auth::getUserID();

// must do Language::setPreference before template is initialized
if ($cat == 'update_account') {
    if (isset($_POST['language'])) {
        $res = User::setLang($usr_id, $_POST['language']);
        Language::setPreference();
    }
}

$tpl = new Template_Helper();
$tpl->setTemplate('preferences.tpl.html');

Auth::checkAuthentication();

if (Auth::isAnonUser()) {
    Auth::redirect('index.php');
}

$res = null;

if ($cat == 'update_account') {
    $preferences = $_POST;

    // if the user is trying to upload a new signature, override any changes to the textarea
    if (!empty($_FILES['file_signature']['name'])) {
        $preferences['email_signature'] = file_get_contents($_FILES['file_signature']['tmp_name']);
    }

    $res = Prefs::set($usr_id, $preferences);
    User::updateSMS($usr_id, @$_POST['sms_email']);
} elseif ($cat == 'update_name') {
    $res = User::updateFullName($usr_id);
} elseif ($cat == 'update_email') {
    $res = User::updateEmail($usr_id);
} elseif ($cat == 'update_password') {
    // verify current password
    if (!Auth::isCorrectPassword(Auth::getUserLogin(), $_POST['password'])) {
        Misc::setMessage(ev_gettext('Incorrect password'), Misc::MSG_ERROR);
        $res = -3;
    } elseif ($_POST['new_password'] != $_POST['confirm_password']) {
        Misc::setMessage(ev_gettext('New passwords mismatch'), Misc::MSG_ERROR);
        $res = -2;
    } elseif ($_POST['password'] == $_POST['new_password']) {
        Misc::setMessage(ev_gettext('Please set different password than current'), Misc::MSG_ERROR);
        $res = -2;
    } else {
        try {
            User::updatePassword($usr_id, $_POST['new_password']);
            $res = 1;
        } catch (Exception $e) {
            error_log($e->getMessage());
            $res = -1;
        }
    }
}

if ($res == 1) {
    Misc::setMessage(ev_gettext('Your information has been updated'));
} elseif ($res !== null) {
    Misc::setMessage(ev_gettext('Sorry, there was an error updating your information'), Misc::MSG_ERROR);
}

$prefs = Prefs::get($usr_id);
$prefs['sms_email'] = User::getSMS($usr_id);

$tpl->assign('user_prefs', $prefs);
$tpl->assign('user_info', User::getDetails($usr_id));
$tpl->assign('assigned_projects', Project::getAssocList($usr_id, false, true));
$tpl->assign('zones', Date_Helper::getTimezoneList());
$tpl->assign('avail_langs', Language::getAvailableLanguages());
$tpl->assign('current_locale', User::getLang($usr_id, true));
$tpl->assign(array(
    'can_update_name' => Auth::canUserUpdateName($usr_id),
    'can_update_email' => Auth::canUserUpdateEmail($usr_id),
    'can_update_password' => Auth::canUserUpdatePassword($usr_id),
));

$tpl->displayTemplate();
