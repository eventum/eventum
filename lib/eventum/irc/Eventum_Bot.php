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
            $irc->setDebugLevel($config['debuglevel']);
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
        $irc->registerTimehandler(1000, $this, 'signalDispatch');

        // methods that keep track of who is authenticated
        $irc->registerActionhandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $this, 'updateAuthenticatedUser');
        $irc->registerActionhandler(
            SMARTIRC_TYPE_KICK | SMARTIRC_TYPE_QUIT | SMARTIRC_TYPE_PART, '.*', $this, 'removeAuthenticatedUser'
        );

        // register bot commands
        $commands = new BotCommands($this, $irc, $this->config);
        $commands->register($irc);
    }

    public function signalDispatch()
    {
        pcntl_signal_dispatch();
    }

    private function joinChannels(Net_SmartIRC $irc)
    {
        foreach ($this->channels as $prj_id => $options) {
            foreach ($options as $chan => $categories) {
                $irc->join($chan);
            }
        }
    }

    public function addUser($data, $email)
    {
        $this->auth[$data->nick] = $email;
    }

    public function getUsers()
    {
        return $this->auth;
    }

    /**
     * @param Net_SmartIRC_data $data
     * @return bool
     */
    public function isAuthenticated(Net_SmartIRC_data $data)
    {
        return in_array($data->nick, array_keys($this->auth));
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

    /**
     * Keep track of nicks for authenticated users
     *
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     */
    public function updateAuthenticatedUser(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        $old_nick = $data->nick;
        $new_nick = $data->message;
        if (in_array($data->nick, array_keys($this->auth))) {
            $this->auth[$new_nick] = $this->auth[$old_nick];
            unset($this->auth[$old_nick]);
        }
    }

    /**
     * Keep track of nicks for authenticated users
     *
     * @param Net_SmartIRC $irc
     * @param Net_SmartIRC_data $data
     */
    public function removeAuthenticatedUser(Net_SmartIRC $irc, Net_SmartIRC_data $data)
    {
        if (in_array($data->nick, array_keys($this->auth))) {
            unset($this->auth[$data->nick]);
        }
    }

    /**
     * Helper method to get the list of channels that should be used in the
     * notifications
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of channels
     */
    public function getChannels($prj_id)
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
    public function getProjectsForChannel($channel)
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
}
