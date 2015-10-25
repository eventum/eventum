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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

class Eventum_Bot
{
    /**
     * List of authenticated users
     *
     * @var array
     */
    private $auth = array();

    /**
     * List of IRC channels where to join, notify and listen for commands
     *
     * @var array
     */
    private $channels = array();

    /**
     * Configuration for the bot
     *
     * @var array
     */
    private $config;

    /**
     * The IRC connection handle
     *
     * @var Net_SmartIRC
     */
    private $irc;

    public function __construct($config)
    {
        $this->config = $config;

        // map project_id => channel(s)
        // TODO: Map old config to new config
        foreach ($config['channels'] as $proj => $chan) {
            $proj_id = Project::getID($proj);

            // we need to map old configs with just channels to new config with categories as well
            if (!is_array($chan)) {
                // old config, one channel
                $options = array(
                    $chan => array($config['default_category']),
                );
            } elseif (isset($chan[0]) and !is_array($chan[0])) {
                // old config with multiple channels
                $options = array();
                foreach ($chan as $individual_chan) {
                    $options[$individual_chan] = array($config['default_category']);
                }
            } else {
                // new format
                $options = $chan;
            }

            $this->channels[$proj_id] = $options;
        }
    }

    /**
     * Create IRC Bot, connect, login and listen for events, and finally disconnect.
     */
    public function run()
    {
        $config = $this->config;
        $this->irc = $irc = new Net_SmartIRC();

        if (isset($config['logfile'])) {
            $irc->setLogdestination(SMARTIRC_FILE);
            $irc->setLogfile($config['logfile']);
        }

        $irc->setUseSockets(true);
        $irc->setAutoReconnect(true);
        $irc->setAutoRetry(true);
        $irc->setReceiveTimeout(600);
        $irc->setTransmitTimeout(600);

        $this->registerHandlers($this->irc);

        $irc->connect($config['hostname'], $config['port']);
        if (empty($config['username'])) {
            $irc->login($config['nickname'], $config['realname']);
        } elseif (empty($config['password'])) {
            $irc->login($config['nickname'], $config['realname'], 0, $config['username']);
        } else {
            $irc->login($config['nickname'], $config['realname'], 0, $config['username'], $config['password']);
        }

        $irc->listen();
        $irc->disconnect();
    }

    private function registerHandlers(Net_SmartIRC $irc)
    {
        $irc->registerTimehandler(3000, $this, 'notifyEvents');

        // methods that keep track of who is authenticated
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-auth', $this, 'listAuthenticatedUsers');
        $irc->registerActionhandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $this, '_updateAuthenticatedUser');
        $irc->registerActionhandler(
            SMARTIRC_TYPE_KICK | SMARTIRC_TYPE_QUIT | SMARTIRC_TYPE_PART, '.*', $this, '_removeAuthenticatedUser'
        );
        $irc->registerActionhandler(SMARTIRC_TYPE_LOGIN, '.*', $this, '_joinChannels');

        // real bot commands
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?help', $this, 'listAvailableCommands');
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?auth ', $this, 'authenticate');
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?clock', $this, 'clockUser');
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-clocked-in', $this, 'listClockedInUsers');
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-quarantined', $this, 'listQuarantinedIssues');
    }

    public function _isAuthenticated(&$irc, &$data)
    {
        if (in_array($data->nick, array_keys($this->auth))) {
            return true;
        }

        $this->sendResponse($irc, $data->nick, 'Error: You need to be authenticated to run this command.');
        return false;
    }

    public function _getEmailByNickname($nickname)
    {
        if (in_array($nickname, array_keys($this->auth))) {
            return $this->auth[$nickname];
        }

        return '';
    }

    public function _getNicknameByUser($usr_id)
    {
        $email = User::getEmail($usr_id);

        $key = array_search($email, $this->auth);
        if ($key) {
            return $key;
        }

        return '';
    }

    public function clockUser(&$irc, &$data)
    {
        if (!$this->_isAuthenticated($irc, $data)) {
            return;
        }
        $email = $this->_getEmailByNickname($data->nick);

        $pieces = explode(' ', $data->message);
        if ((count($pieces) == 2) && ($pieces[1] != 'in') && ($pieces[1] != 'out')) {
            $this->sendResponse(
                $irc, $data->nick, 'Error: wrong parameter count for "CLOCK" command. Format is "!clock [in|out]".'
            );

            return;
        }
        if (@$pieces[1] == 'in') {
            $res = User::clockIn(User::getUserIDByEmail($email));
        } elseif (@$pieces[1] == 'out') {
            $res = User::clockOut(User::getUserIDByEmail($email));
        } else {
            if (User::isClockedIn(User::getUserIDByEmail($email))) {
                $msg = 'clocked in';
            } else {
                $msg = 'clocked out';
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

    public function listClockedInUsers(&$irc, &$data)
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

    public function listQuarantinedIssues(&$irc, &$data)
    {
        if (!$this->_isAuthenticated($irc, $data)) {
            return;
        }

        $list = Issue::getQuarantinedIssueList();
        if (count($list) == 0) {
            $this->sendResponse($irc, $data->nick, 'There are no quarantined issues as of now.');
        } else {
            $this->sendResponse(
                $irc, $data->nick, 'The following are the details of the ' . count($list) . ' quarantined issue(s):'
            );
            for ($i = 0; $i < count($list); $i++) {
                $url = APP_BASE_URL . 'view.php?id=' . $list[$i]['iss_id'];
                $msg = sprintf(
                    'Issue #%d: %s, Assignment: %s, %s', $list[$i]['iss_id'], $list[$i]['iss_summary'],
                    $list[$i]['assigned_users'], $url
                );
                $this->sendResponse($irc, $data->nick, $msg);
            }
        }
    }

    public function listAvailableCommands(&$irc, &$data)
    {
        $commands = array(
            'auth' => 'Format is "auth user@example.com password"',
            'clock' => 'Format is "clock [in|out]"',
            'list-clocked-in' => 'Format is "list-clocked-in"',
            'list-quarantined' => 'Format is "list-quarantined"',
        );
        $this->sendResponse($irc, $data->nick, 'This is the list of available commands:');
        foreach ($commands as $command => $description) {
            $this->sendResponse($irc, $data->nick, "$command: $description");
        }
    }

    public function _updateAuthenticatedUser(&$irc, &$data)
    {
        $old_nick = $data->nick;
        $new_nick = $data->message;
        if (in_array($data->nick, array_keys($this->auth))) {
            $this->auth[$new_nick] = $this->auth[$old_nick];
            unset($this->auth[$old_nick]);
        }
    }

    public function _removeAuthenticatedUser(&$irc, &$data)
    {
        if (in_array($data->nick, array_keys($this->auth))) {
            unset($this->auth[$data->nick]);
        }
    }

    public function listAuthenticatedUsers(&$irc, &$data)
    {
        foreach ($this->auth as $nickname => $email) {
            $this->sendResponse($irc, $data->nick, "$nickname => $email");
        }
    }

    public function authenticate(&$irc, &$data)
    {
        $pieces = explode(' ', $data->message);
        if (count($pieces) != 3) {
            $this->sendResponse(
                $irc, $data->nick,
                'Error: wrong parameter count for "AUTH" command. Format is "!auth user@example.com password".'
            );

            return;
        }

        $email = $pieces[1];
        $password = $pieces[2];

        // check if the email exists
        if (!Auth::userExists($email)) {
            $this->sendResponse(
                $irc, $data->nick, 'Error: could not find a user account for the given email address "$email".'
            );

            return;
        }

        // check if the given password is correct
        if (!Auth::isCorrectPassword($email, $password)) {
            $this->sendResponse(
                $irc, $data->nick, 'Error: The email address / password combination could not be found in the system.'
            );

            return;
        }

        // check if the user account is activated
        if (!Auth::isActiveUser($email)) {
            $this->sendResponse(
                $irc, $data->nick,
                'Error: Your user status is currently set as inactive. Please contact your local system administrator for further information.'
            );

            return;
        }

        $auth[$data->nick] = $email;
        $this->sendResponse($irc, $data->nick, 'Thank you, you have been successfully authenticated.');
    }

    /**
     * Helper method to get the list of channels that should be used in the
     * notifications
     *
     * @access  private
     * @param   integer $prj_id The project ID
     * @return  array The list of channels
     */
    public function _getChannels($prj_id)
    {
        if (isset($this->channels[$prj_id])) {
            return $this->channels[$prj_id];
        }

        return array();
    }

    /**
     * Helper method to the projects a channel displays messages for.
     *
     * @access  private
     * @param   string $channel The name of the channel
     * @return  array The projects displayed in the channel
     */
    public function _getProjectsForChannel($channel)
    {
        $projects = array();
        foreach ($this->channels as $prj_id => $prj_channels) {
            foreach ($prj_channels as $prj_channel) {
                if ($prj_channel == $channel) {
                    $projects[] = $prj_id;
                }
            }
        }

        return $projects;
    }

    /**
     * Method used as a callback to send notification events to the proper
     * recipients.
     *
     * @access  public
     * @param   resource $irc The IRC connection handle
     * @return  void
     */
    public function notifyEvents(&$irc)
    {
        // check the message table
        $stmt
            = "SELECT
                    ino_id,
                    ino_iss_id,
                    ino_prj_id,
                    ino_message,
                    ino_target_usr_id,
                    ino_category
                 FROM
                    {{%irc_notice}}
                 LEFT JOIN
                    {{%issue}}
                 ON
                    iss_id=ino_iss_id
                 WHERE
                    ino_status='pending'";
        $res = DB_Helper::getInstance()->getAll($stmt);
        for ($i = 0; $i < count($res); $i++) {
            if (empty($res[$i]['ino_category'])) {
                $res[$i]['ino_category'] = APP_EVENTUM_IRC_CATEGORY_DEFAULT;
            }

            // check if this is a targeted message
            if (!empty($res[$i]['ino_target_usr_id'])) {
                $nick = $this->_getNicknameByUser($res[$i]['ino_target_usr_id']);
                if (!empty($nick)) {
                    $this->sendResponse($irc, $nick, $res[$i]['ino_message']);
                }
                $this->_markEventSent($res[$i]['ino_id']);
                continue;
            }

            $channels = $this->_getChannels($res[$i]['ino_prj_id']);
            if (count($channels) > 0) {
                foreach ($channels as $channel => $categories) {
                    $message = $res[$i]['ino_message'];
                    if ($res[$i]['ino_iss_id'] > 0) {
                        $message .= ' - ' . APP_BASE_URL . 'view.php?id=' . $res[$i]['ino_iss_id'];
                    } elseif (substr($res[$i]['ino_message'], 0, strlen('New Pending Email')) == 'New Pending Email') {
                        $message .= ' - ' . APP_BASE_URL . 'emails.php';
                    }
                    if (count($this->_getProjectsForChannel($channel)) > 1) {
                        // if multiple projects display in the same channel, display project in message
                        $message = '[' . Project::getName($res[$i]['ino_prj_id']) . '] ' . $message;
                    }
                    if (in_array($res[$i]['ino_category'], $categories)) {
                        $this->sendResponse($irc, $channel, $message);
                    }
                }
                $this->_markEventSent($res[$i]['ino_id']);
            }
        }
    }

    private function _markEventSent($ino_id)
    {
        // mark message as sent
        $stmt
            = "UPDATE
                    {{%irc_notice}}
                 SET
                    ino_status='sent'
                 WHERE
                    ino_id=?";
        DB_Helper::getInstance()->query($stmt, array($ino_id));
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
    public function sendResponse(&$irc, $target, $response)
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
            $irc->message($type, $target, $line, SMARTIRC_CRITICAL);
            sleep(1);
        }
    }

    public function _joinChannels(&$irc)
    {
        foreach ($this->channels as $prj_id => $options) {
            foreach ($options as $chan => $categories) {
                $irc->join($chan);
            }
        }
    }
}
