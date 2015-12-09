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
            Logger::app()->error($e);
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
