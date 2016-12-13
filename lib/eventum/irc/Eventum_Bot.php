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

use Eventum\ConcurrentLock;

class Eventum_Bot
{
    /**
     * @var bool flag indicating that we should shut down
     */
    public $shutdown = false;
    /**
     * List of authenticated users
     *
     * @var array
     */
    private $auth = [];

    /**
     * List of IRC channels where to join, notify and listen for commands
     *
     * @var array
     */
    private $channels = [];

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

    /** @var bool */
    private $have_pcntl = false;

    public function __construct()
    {
        $this->config = $config = $this->getConfig();
        $this->have_pcntl = function_exists('pcntl_signal');

        // map project_id => channel(s)
        foreach ($config['channels'] as $proj => $chan) {
            $proj_id = Project::getID($proj);

            // we need to map old configs with just channels to new config with categories as well
            if (!is_array($chan)) {
                // old config, one channel
                $options = [
                    $chan => [$config['default_category']],
                ];
            } elseif (isset($chan[0]) and !is_array($chan[0])) {
                // old config with multiple channels
                $options = [];
                foreach ($chan as $individual_chan) {
                    $options[$individual_chan] = [$config['default_category']];
                }
            } else {
                // new format
                $options = $chan;
            }

            $this->channels[$proj_id] = $options;
        }
    }

    /**
     * Read in config/irc_config.php
     *
     * @return array
     */
    private function getConfig()
    {
        $config_file = APP_CONFIG_PATH . '/irc_config.php';
        if (!file_exists($config_file)) {
            throw new InvalidArgumentException("Config file '$config_file' does not exist");
        }

        $default_config = [
            'default_category' => APP_EVENTUM_IRC_CATEGORY_DEFAULT,
            'lock' => 'irc_bot',

            // smartirc logger
            'logfile' => APP_LOG_PATH . '/irc_bot_smartirc.log',
            // error logs
            'error_log' => APP_LOG_PATH . '/irc_bot_error.log',

            /**
             * Bitwise debug level out of SMARTIRC_DEBUG_* constants
             *
             * @see Net_SmartIRC::setDebugLevel
             */
            'debuglevel' => SMARTIRC_DEBUG_NOTICE
        ];

        $config = require $config_file;
        if ($config == 1) {
            // handle legacy config format
            /** @var string $irc_server_hostname */
            /** @var int $irc_server_port */
            /** @var string $nickname */
            /** @var string $realname */
            /** @var string $username */
            /** @var string $password */
            /** @var array $irc_channels */
            $config = [
                'hostname' => $irc_server_hostname,
                'port' => $irc_server_port,
                'nickname' => $nickname,
                'realname' => $realname,
                'username' => $username,
                'password' => $password,
                'channels' => $irc_channels,
            ];
        }

        return array_merge($default_config, $config);
    }

    /**
     * setup event handlers to cleanup on shutdown
     */
    public function cleanup()
    {
        // setup signal handler to shutdown cleanly
        $handler = function ($signal = null) {
            $this->shutdown = true;
            // if stream_select receives signal, SmartIRC will automatically retry
            // disable reconnect, and die
            // this is not needed if we are connected,
            // but unable to query such state, all variables and methods related to it are not public
            $this->irc->setAutoRetry(false);

            if ($signal) {
                $this->irc->log(SMARTIRC_DEBUG_NOTICE, "Got signal[$signal]; shutdown", __FILE__, __LINE__);
                $this->irc->quit('Terminated');
            } else {
                $this->irc->log(SMARTIRC_DEBUG_NOTICE, 'Shutdown handler', __FILE__, __LINE__);
                $this->irc->quit('Bye');
            }

            // QUIT has no effect if not connected
            $this->irc->disconnect();
        };

        if ($this->have_pcntl) {
            pcntl_signal(SIGINT, $handler);
            pcntl_signal(SIGTERM, $handler);
        } else {
            error_log('pcntl extension not present, signal processing not enabled');
        }

        // NOTE: signal handler is not enough because stream_select() also catches the signals and aborts the process
        // so register the shutdown handler as well
        register_shutdown_function($handler);
    }

    public function run()
    {
        $lock = new ConcurrentLock($this->config['lock']);
        $lock->synchronized(
            function () {
                $this->execute();
            }
        );
    }

    /**
     * Create IRC Bot, connect, login and listen for events, and finally disconnect.
     */
    private function execute()
    {
        $config = $this->config;

        // setup logging
        // redirect stderr to a log
        ini_set('log_errors', 'On');
        ini_set('error_log', $config['error_log']);

        $this->irc = $irc = new Net_SmartIRC();

        if (isset($config['debuglevel'])) {
            $irc->setDebugLevel($config['debuglevel']);
        }

        if (isset($config['logfile'])) {
            $irc->setLogDestination(SMARTIRC_FILE);
            $irc->setLogFile($config['logfile']);
        }

        // reconnect is poorly designed, do not use it
        // @see https://pear.php.net/bugs/bug.php?id=20974
        //$irc->setAutoRetry(true);
        $irc->setAutoRetry(false);
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

        // loop forever, reconnect and retry
        // @see https://pear.php.net/bugs/bug.php?id=20974
        while (!$this->shutdown) {
            $irc->listen();
            $irc->reconnect();
        }

        $irc->disconnect();
    }

    /**
     * @param Net_SmartIRC $irc
     */
    private function registerHandlers(Net_SmartIRC $irc)
    {
        // doing it cleanly with dispatch is not possible currently
        // @see http://pear.php.net/bugs/bug.php?id=20973
        if ($this->have_pcntl) {
            declare(ticks=1);
            //$irc->registerTimehandler(1000, $this, 'signalDispatch');
        }

        // methods that keep track of who is authenticated
        $irc->registerActionHandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $this, 'updateAuthenticatedUser');
        $irc->registerActionHandler(
            SMARTIRC_TYPE_KICK | SMARTIRC_TYPE_QUIT | SMARTIRC_TYPE_PART, '.*', $this, 'removeAuthenticatedUser'
        );

        // register bot commands
        $commands = new BotCommands($this, $irc, $this->config);
        $commands->register($irc);
    }

    public function signalDispatch(Net_SmartIRC $irc)
    {
        $irc->log(SMARTIRC_DEBUG_ACTIONHANDLER, 'signal dispatch', __FILE__, __LINE__);
        pcntl_signal_dispatch();
    }

    /**
     * Join configured channels.
     * If channel name contains space, everything after space is considered channel key.
     *
     * @param Net_SmartIRC $irc
     */
    private function joinChannels(Net_SmartIRC $irc)
    {
        foreach ($this->channels as $prj_id => $options) {
            foreach ($options as $chan => $categories) {
                $parts = explode(' ', $chan, 2);
                if (count($parts) > 1) {
                    // join with key
                    $irc->join($parts[0], $parts[1]);
                } else {
                    $irc->join($chan);
                }
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

        return [];
    }

    /**
     * Helper method to the projects a channel displays messages for.
     *
     * @param   string $channel The name of the channel
     * @return  array The projects displayed in the channel
     */
    public function getProjectsForChannel($channel)
    {
        $projects = [];
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
