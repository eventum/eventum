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

    /**
     * @param array $config
     */
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
     * acquire a lock to prevent multiple scripts from running at the same time.
     * if the lock was acquired, setup event handler to release lock on shutdown
     *
     * @param bool|true $check
     * @return bool
     */
    public function lock($check = true)
    {
        $locked = Lock::acquire($this->config['lock'], $check);

        if (!$locked) {
            return $locked;
        }

        // setup signal handler to be able to remove lock and shutdown cleanly
        $bot = $this;
        $irc = &$this->irc;
        $handler = function ($signal = null) use ($bot, &$irc) {
            if ($signal) {
                $irc->log(SMARTIRC_DEBUG_NOTICE, "Got signal[$signal]; shutdown", __FILE__, __LINE__);
                $irc->quit('Terminated');
            } else {
                $irc->log(SMARTIRC_DEBUG_NOTICE, "shutdown handler", __FILE__, __LINE__);
                $irc->quit('Bye');
            }

            $bot->unlock();
        };

        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGTERM, $handler);

        // NOTE: signal handler is not enough because stream_select() also catches the signals and aborts the process
        // so register the shutdown handler as well
        register_shutdown_function($handler);

        return $locked;
    }

    public function unlock()
    {
        Lock::release($this->config['lock']);
    }

    /**
     * Create IRC Bot, connect, login and listen for events, and finally disconnect.
     */
    public function run()
    {
        $config = $this->config;
        $this->irc = $irc = new Net_SmartIRC();

        if (isset($config['debuglevel'])) {
            $const = "SMARTIRC_DEBUG_" . strtoupper($config['debuglevel']);
            if (!defined($const)) {
                throw new InvalidArgumentException("Bad value for debuglevel: {$config['debuglevel']}");
            }
            $irc->setDebugLevel(constant($const));
        }

        if (isset($config['logfile'])) {
            $irc->setLogdestination(SMARTIRC_FILE);
            $irc->setLogfile($config['logfile']);
        }

        $irc->setAutoRetry(true);
        $irc->setAutoRetryMax(PHP_INT_MAX);
        $irc->setReconnectDelay(10000);
        $irc->setReceiveTimeout(600);
        $irc->setTransmitTimeout(600);

        // enable user and channel syncing,
        // users are accessible via $irc->user array, i.e $irc->user['meebey']->host;
        $irc->setChannelSyncing(true);
        $irc->setUserSyncing(true);

        $this->registerHandlers($this->irc);

        $irc->connect($config['hostname'], $config['port']);
        if (empty($config['username'])) {
            $irc->login($config['nickname'], $config['realname']);
        } elseif (empty($config['password'])) {
            $irc->login($config['nickname'], $config['realname'], 0, $config['username']);
        } else {
            $irc->login($config['nickname'], $config['realname'], 0, $config['username'], $config['password']);
        }

        $this->joinChannels($irc);

        $irc->listen();
        $irc->disconnect();
    }

    /**
     * @param Net_SmartIRC $irc
     */
    private function registerHandlers(Net_SmartIRC $irc)
    {
        $irc->registerTimehandler(3000, $this, 'notifyEvents');
        $irc->registerTimehandler(1000, $this, 'signalDispatch');

        // methods that keep track of who is authenticated
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-auth', $this, 'listAuthenticatedUsers');
        $irc->registerActionhandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $this, 'updateAuthenticatedUser');
        $irc->registerActionhandler(
            SMARTIRC_TYPE_KICK | SMARTIRC_TYPE_QUIT | SMARTIRC_TYPE_PART, '.*', $this, 'removeAuthenticatedUser'
        );

        // real bot commands
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?help', $this, 'listAvailableCommands');
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?auth ', $this, 'authenticate');
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?clock', $this, 'clockUser');
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-clocked-in', $this, 'listClockedInUsers');
        $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-quarantined', $this, 'listQuarantinedIssues');
    }

    public function signalDispatch()
    {
        pcntl_signal_dispatch();
    }

    /**
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     * @return bool
     */
    private function isAuthenticated(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (in_array($data->nick, array_keys($this->auth))) {
            return true;
        }

        $this->sendResponse($data->nick, 'Error: You need to be authenticated to run this command.');
        return false;
    }

    public function getEmailByNickname($nickname)
    {
        if (in_array($nickname, array_keys($this->auth))) {
            return $this->auth[$nickname];
        }

        return null;
    }

    public function getNicknameByUser($usr_id)
    {
        $email = User::getEmail($usr_id);

        $key = array_search($email, $this->auth);
        if ($key) {
            return $key;
        }

        return null;
    }

    public function clockUser(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (!$this->isAuthenticated($irc, $data)) {
            return;
        }
        // FIXME: handle if $email is empty
        $email = $this->getEmailByNickname($data->nick);

        $pieces = explode(' ', $data->message);
        if ((count($pieces) == 2) && ($pieces[1] != 'in') && ($pieces[1] != 'out')) {
            $this->sendResponse($data->nick, 'Error: wrong parameter count for "CLOCK" command. Format is "!clock [in|out]".');

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
            $this->sendResponse($data->nick, "You are currently $msg.");

            return;
        }
        if ($res == 1) {
            $this->sendResponse($data->nick, 'Thank you, you are now clocked ' . $pieces[1] . '.');
        } else {
            $this->sendResponse($data->nick, 'Error clocking ' . $pieces[1] . '.');
        }
    }

    public function listClockedInUsers(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (!$this->isAuthenticated($irc, $data)) {
            return;
        }

        $list = User::getClockedInList();
        if (count($list) == 0) {
            $this->sendResponse($data->nick, 'There are no clocked-in users as of now.');
            return;
        }

        $this->sendResponse($data->nick, 'The following is the list of clocked-in users:');
        foreach ($list as $name => $email) {
            $this->sendResponse($data->nick, "$name: $email");
        }
    }

    public function listQuarantinedIssues(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (!$this->isAuthenticated($irc, $data)) {
            return;
        }

        $list = Issue::getQuarantinedIssueList();
        $count = count($list);
        if ($count == 0) {
            $this->sendResponse($data->nick, 'There are no quarantined issues as of now.');
            return;
        }

        $this->sendResponse($data->nick, "The following are the details of the {$count} quarantined issue(s):");
        foreach ($list as $row) {
            $url = APP_BASE_URL . 'view.php?id=' . $row['iss_id'];
            $msg = sprintf(
                'Issue #%d: %s, Assignment: %s, %s', $row['iss_id'], $row['iss_summary'],
                $row['assigned_users'], $url
            );
            $this->sendResponse($data->nick, $msg);
        }
    }

    public function listAvailableCommands(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        $commands = array(
            'auth' => 'Format is "auth user@example.com password"',
            'clock' => 'Format is "clock [in|out]"',
            'list-clocked-in' => 'Format is "list-clocked-in"',
            'list-quarantined' => 'Format is "list-quarantined"',
        );

        $this->sendResponse($data->nick, 'This is the list of available commands:');
        foreach ($commands as $command => $description) {
            $this->sendResponse($data->nick, "$command: $description");
        }
    }

    public function updateAuthenticatedUser(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        $old_nick = $data->nick;
        $new_nick = $data->message;
        if (in_array($data->nick, array_keys($this->auth))) {
            $this->auth[$new_nick] = $this->auth[$old_nick];
            unset($this->auth[$old_nick]);
        }
    }

    public function removeAuthenticatedUser(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (in_array($data->nick, array_keys($this->auth))) {
            unset($this->auth[$data->nick]);
        }
    }

    public function listAuthenticatedUsers(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        foreach ($this->auth as $nickname => $email) {
            $this->sendResponse($data->nick, "$nickname => $email");
        }
    }

    public function authenticate(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        $pieces = explode(' ', $data->message);
        if (count($pieces) != 3) {
            $this->sendResponse($data->nick, 'Error: wrong parameter count for "AUTH" command. Format is "!auth user@example.com password".');

            return;
        }

        $email = $pieces[1];
        $password = $pieces[2];

        // check if the email exists
        if (!Auth::userExists($email)) {
            $this->sendResponse($data->nick, 'Error: could not find a user account for the given email address "$email".');

            return;
        }

        // check if the given password is correct
        if (!Auth::isCorrectPassword($email, $password)) {
            $this->sendResponse($data->nick, 'Error: The email address / password combination could not be found in the system.');

            return;
        }

        // check if the user account is activated
        if (!Auth::isActiveUser($email)) {
            $this->sendResponse($data->nick, 'Error: Your user status is currently set as inactive. Please contact your local system administrator for further information.');

            return;
        }

        $this->auth[$data->nick] = $email;
        $this->sendResponse($data->nick, 'Thank you, you have been successfully authenticated.');
    }

    /**
     * Helper method to get the list of channels that should be used in the
     * notifications
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of channels
     */
    private function getChannels($prj_id)
    {
        if (isset($this->channels[$prj_id])) {
            return $this->channels[$prj_id];
        }

        return array();
    }

    /**
     * Helper method to the projects a channel displays messages for.
     *
     * @param   string $channel The name of the channel
     * @return  array The projects displayed in the channel
     */
    private function getProjectsForChannel($channel)
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
     * @param   Net_SmartIRC $irc The IRC connection handle
     */
    public function notifyEvents(Net_SmartIRC $irc)
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
                    ino_status=?";
        $res = DB_Helper::getInstance()->getAll($stmt, array('pending'));
        foreach ($res as $row) {
            if (empty($row['ino_category'])) {
                $row['ino_category'] = $this->config['default_category'];
            }

            // check if this is a targeted message
            if (!empty($row['ino_target_usr_id'])) {
                $nick = $this->getNicknameByUser($row['ino_target_usr_id']);
                if ($nick) {
                    $this->sendResponse($nick, $row['ino_message']);
                }
                // FIXME: why mark it sent if user is not online?
                $this->markEventSent($row['ino_id']);
                continue;
            }

            $channels = $this->getChannels($row['ino_prj_id']);
            if (!$channels) {
                continue;
            }
            foreach ($channels as $channel => $categories) {
                $message = $row['ino_message'];
                if ($row['ino_iss_id'] > 0) {
                    $message .= ' - ' . APP_BASE_URL . 'view.php?id=' . $row['ino_iss_id'];
                } elseif (substr($row['ino_message'], 0, strlen('New Pending Email')) == 'New Pending Email') {
                    $message .= ' - ' . APP_BASE_URL . 'emails.php';
                }
                if (count($this->getProjectsForChannel($channel)) > 1) {
                    // if multiple projects display in the same channel, display project in message
                    $message = '[' . Project::getName($row['ino_prj_id']) . '] ' . $message;
                }
                if (in_array($row['ino_category'], $categories)) {
                    $this->sendResponse($channel, $message);
                }
            }
            $this->markEventSent($row['ino_id']);
        }
    }

    private function markEventSent($ino_id)
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
     * @param string $target The target for this message
     * @param string|string[] $response The message to send
     * @param integer $priority the priority level of the message
     */
    public function sendResponse($target, $response, $priority = SMARTIRC_MEDIUM)
    {
        if (substr($target, 0, 1) != '#') {
            $type = SMARTIRC_TYPE_QUERY;
        } else {
            $type = SMARTIRC_TYPE_CHANNEL;
        }
        $this->irc->message($type, $target, $response, $priority);
    }

    private function joinChannels(Net_SmartIRC $irc)
    {
        foreach ($this->channels as $prj_id => $options) {
            foreach ($options as $chan => $categories) {
                $irc->join($chan);
            }
        }
    }
}
