<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
//
// @(#) $Id: $
//

include_once("../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_PEAR_PATH . 'Net/SmartIRC.php');

// SETUP: need to change the project name in here
$channels = array(
    Project::getID('Default Project') => array(
        '#issues',
    )
);

$auth = array();

class Eventum_Bot
{
    function _isAuthenticated(&$irc, &$data)
    {
        global $auth;

        if (in_array($data->nick, array_keys($auth))) {
            return true;
        } else {
            $this->sendResponse($irc, $data->nick, 'Error: You need to be authenticated to run this command.');
            return false;
        }
    }


    function _getEmailByNickname($nickname)
    {
        global $auth;

        if (in_array($nickname, array_keys($auth))) {
            return $auth[$nickname];
        } else {
            return '';
        }
    }


    function clockUser(&$irc, &$data)
    {
        if (!$this->_isAuthenticated($irc, $data)) {
            return;
        }
        $email = $this->_getEmailByNickname($data->nick);

        $pieces = explode(' ', $data->message);
        if ((count($pieces) == 2) && ($pieces[1] != 'in') && ($pieces[1] != 'out')) {
            $this->sendResponse($irc, $data->nick, 'Error: wrong parameter count for "CLOCK" command. Format is "!clock [in|out]".');
            return;
        }
        if (@$pieces[1] == 'in') {
            $res = User::clockIn(User::getUserIDByEmail($email));
        } elseif (@$pieces[1] == 'out') {
            $res = User::clockOut(User::getUserIDByEmail($email));
        } else {
            if (User::isClockedIn(User::getUserIDByEmail($email))) {
                $msg = "clocked in";
            } else {
                $msg = "clocked out";
            }
            $this->sendResponse($irc, $data->nick, "You are currently $msg.");
            return;
        }
        if ($res == 1) {
            $this->sendResponse($irc, $data->nick, 'Thank you, you are now clocked ' . $pieces[1] . '.');
        } else {
            $this->sendResponse($irc, $data->nick, 'Error clocking ' . $pieces[1] . '.');
        }
    }


    function listClockedInUsers(&$irc, &$data)
    {
        if (!$this->_isAuthenticated($irc, $data)) {
            return;
        }

        $list = User::getClockedInList();
        if (count($list) == 0) {
            $this->sendResponse($irc, $data->nick, 'There are no clocked-in users as of now.');
        } else {
            $this->sendResponse($irc, $data->nick, 'The following is the list of clocked-in users:');
            foreach ($list as $name => $email) {
                $this->sendResponse($irc, $data->nick, "$name: $email");
            }
        }
    }


    function listQuarantinedIssues(&$irc, &$data)
    {
        if (!$this->_isAuthenticated($irc, $data)) {
            return;
        }

        $list = Issue::getQuarantinedIssueList();
        if (count($list) == 0) {
            $this->sendResponse($irc, $data->nick, 'There are no quarantined issues as of now.');
        } else {
            $this->sendResponse($irc, $data->nick, 'The following are the details of the ' . count($list) . ' quarantined issue(s):');
            for ($i = 0; $i < count($list); $i++) {
                $url = APP_BASE_URL . 'view.php?id=' . $list[$i]['iss_id'];
                $msg = sprintf('Issue #%d: %s, Assignment: %s, %s', $list[$i]['iss_id'], $list[$i]['iss_summary'], $list[$i]['assigned_users'], $url);
                $this->sendResponse($irc, $data->nick, $msg);
            }
        }
    }


    function listAvailableCommands(&$irc, &$data)
    {
        $commands = array(
            'auth'             => 'Format is "auth user@example.com password"',
            'clock'            => 'Format is "clock [in|out]"',
            'list-clocked-in'  => 'Format is "list-clocked-in"',
            'list-quarantined' => 'Format is "list-quarantined"'
        );
        $this->sendResponse($irc, $data->nick, "This is the list of available commands:");
        foreach ($commands as $command => $description) {
            $this->sendResponse($irc, $data->nick, "$command: $description");
        }
    }


    function _updateAuthenticatedUser(&$irc, &$data)
    {
        global $auth;

        $old_nick = $data->nick;
        $new_nick = $data->message;
        if (in_array($data->nick, array_keys($auth))) {
            $auth[$new_nick] = $auth[$old_nick];
            unset($auth[$old_nick]);
        }
    }


    function _removeAuthenticatedUser(&$irc, &$data)
    {
        global $auth;

        if (in_array($data->nick, array_keys($auth))) {
            unset($auth[$data->nick]);
        }
    }


    function listAuthenticatedUsers(&$irc, &$data)
    {
        global $auth;

        foreach ($auth as $nickname => $email) {
            $this->sendResponse($irc, $data->nick, "$nickname => $email");
        }
    }


    function authenticate(&$irc, &$data)
    {
        global $auth;

        $pieces = explode(' ', $data->message);
        if (count($pieces) != 3) {
            $this->sendResponse($irc, $data->nick, 'Error: wrong parameter count for "AUTH" command. Format is "!auth user@example.com password".');
            return;
        }
        $email = $pieces[1];
        $password = $pieces[2];
        // check if the email exists
        if (!Auth::userExists($email)) {
            $this->sendResponse($irc, $data->nick, 'Error: could not find a user account for the given email address "$email".');
            return;
        }
        // check if the given password is correct
        if (!Auth::isCorrectPassword($email, $password)) {
            $this->sendResponse($irc, $data->nick, 'Error: The email address / password combination could not be found in the system.');
            return;
        }
        // check if the user account is activated
        if (!Auth::isActiveUser($email)) {
            $this->sendResponse($irc, $data->nick, 'Error: Your user status is currently set as inactive. Please contact your local system administrator for further information.');
            return;
        } else {
            $auth[$data->nick] = $email;
            $this->sendResponse($irc, $data->nick, 'Thank you, you have been successfully authenticated.');
            return;
        }
    }


    /**
     * Helper method to get the list of channels that should be used in the
     * notifications
     *
     * @access  private
     * @param   integer $prj_id The project ID
     * @return  array The list of channels
     */
    function _getChannels($prj_id)
    {
        global $channels;
        return $channels[$prj_id];
    }


    /**
     * Method used as a callback to send notification events to the proper
     * recipients.
     *
     * @access  public
     * @param   resource $irc The IRC connection handle
     * @return  void
     */
    function notifyEvents(&$irc)
    {
        // check the message table
        $stmt = "SELECT
                    ino_id,
                    ino_iss_id,
                    iss_prj_id,
                    ino_message
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "irc_notice
                 WHERE
                    iss_id=ino_iss_id AND
                    ino_status='pending'";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        for ($i = 0; $i < count($res); $i++) {
            $channels = $this->_getChannels($res[$i]['iss_prj_id']);
            if (count($channels) > 0) {
                foreach ($channels as $channel) {
                    $res[$i]['ino_message'] .= ' - ' . APP_BASE_URL . 'view.php?id=' . $res[$i]['ino_iss_id'];
                    $this->sendResponse($irc, $channel, $res[$i]['ino_message']);
                }
                // mark message as sent
                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "irc_notice
                         SET
                            ino_status='sent'
                         WHERE
                            ino_id=" . $res[$i]['ino_id'];
                $GLOBALS["db_api"]->dbh->query($stmt);
            }
        }
    }


    /**
     * Method used to send a message to the given target.
     *
     * @access  public
     * @param   resource $irc The IRC connection handle
     * @param   string $target The target for this message
     * @param   string $response The message to send
     * @return  void
     */
    function sendResponse(&$irc, $target, $response)
    {
        // XXX: need way to handle messages with length bigger than 255 chars
        if (!is_array($response)) {
            $response = array($response);
        }
        foreach ($response as $line) {
            if (substr($target, 0, 1) != '#') {
                $type = SMARTIRC_TYPE_QUERY;
            } else {
                $type = SMARTIRC_TYPE_CHANNEL;
            }
            $irc->message($type, $target, $response, SMARTIRC_CRITICAL);
            sleep(1);
        }
    }
}

$bot = &new Eventum_Bot();
$irc = &new Net_SmartIRC();
$irc->setDebug(SMARTIRC_DEBUG_ALL);
$irc->setLogdestination(SMARTIRC_FILE);
$irc->setLogfile('irclog.txt');
$irc->setUseSockets(TRUE);
$irc->setAutoReconnect(TRUE);
$irc->setAutoRetry(TRUE);

// register saytime() to be called every 30 sec. (30,000 milliseconds)
$irc->registerTimehandler(3000, $bot, 'notifyEvents');

// methods that keep track of who is authenticated
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-auth', $bot, 'listAuthenticatedUsers');
$irc->registerActionhandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $bot, '_updateAuthenticatedUser');
$irc->registerActionhandler(SMARTIRC_TYPE_KICK|SMARTIRC_TYPE_QUIT|SMARTIRC_TYPE_PART, '.*', $bot, '_removeAuthenticatedUser');

// real bot commands
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?help', $bot, 'listAvailableCommands');
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?auth ', $bot, 'authenticate');
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?clock', $bot, 'clockUser');
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-clocked-in', $bot, 'listClockedInUsers');
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-quarantined', $bot, 'listQuarantinedIssues');


$irc->connect('localhost', 6667);
$irc->login('EventumBOT', 'EventumBOT', 0, 'EventumBOT');
foreach ($channels as $prj_id => $channel_list) {
    $irc->join($channel_list);
}
$irc->listen();
$irc->disconnect();
?>