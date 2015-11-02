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

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('manage/general.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_REPORTER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}
$tpl->assign('project_list', Project::getAll());

if (@$_POST['cat'] == 'update') {
    $smtp = $_POST['smtp'];
    $smtp['auth'] = (bool)$smtp['auth'];
    $setup = array(
        'tool_caption' => $_POST['tool_caption'],
        'support_email' => $_POST['support_email'],
        'description_email_0' => $_POST['description_email_0'],
        'spell_checker' => $_POST['spell_checker'],
        'irc_notification' => $_POST['irc_notification'],
        'update' => (bool)Misc::ifSet($_POST, 'update'),
        'closed' => (bool)Misc::ifSet($_POST, 'closed'),
        'emails' => (bool)Misc::ifSet($_POST, 'emails'),
        'files' => (bool)Misc::ifSet($_POST, 'files'),
        'smtp' => $smtp,
        'open_signup' => $_POST['open_signup'],
        'accounts_projects' => isset($_POST['accounts_projects']) ? $_POST['accounts_projects'] : null,
        'accounts_role' => isset($_POST['accounts_role']) ? $_POST['accounts_role'] : null,
        'subject_based_routing' => $_POST['subject_based_routing'],
        'email_routing' => $_POST['email_routing'],
        'note_routing' => $_POST['note_routing'],
        'draft_routing' => $_POST['draft_routing'],
        'email_error' => $_POST['email_error'],
        'email_reminder' => $_POST['email_reminder'],
        'handle_clock_in' => $_POST['handle_clock_in'],
    );
    $res = Setup::save($setup);
    $tpl->assign('result', $res);

    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the setup information was saved successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext(
                "ERROR: The system doesn't have the appropriate permissions to create the configuration file in the setup directory (%1\$s). ".
                'Please contact your local system administrator and ask for write privileges on the provided path.', APP_CONFIG_PATH),
                Misc::MSG_NOTE_BOX),
            -2  =>  array(ev_gettext(
                "ERROR: The system doesn't have the appropriate permissions to update the configuration file in the setup directory (%1\$s). ".
                'Please contact your local system administrator and ask for write privileges on the provided filename.', APP_SETUP_FILE),
                Misc::MSG_NOTE_BOX),
    ));
}

$tpl->assign(array(
    'setup' => Setup::get(),
    'user_roles' => User::getRoles(array('Customer')),
));

$tpl->displayTemplate();
