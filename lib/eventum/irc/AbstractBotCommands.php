<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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

/**
 * Class containing helper methods for BotCommands class
 */
class AbstractBotCommands
{
    /** @var Eventum_Bot */
    protected $bot;

    /** @var Net_SmartIRC */
    protected $irc;

    /** @var int */
    protected $default_category;

    public function __construct(Eventum_Bot $bot, Net_SmartIRC $irc, array $config)
    {
        $this->bot = $bot;
        $this->irc = $irc;
        $this->default_category = $config['default_category'];
    }

    /**
     * Method used to send a message to the given target.
     *
     * @param string $target The target for this message
     * @param string|string[] $response The message to send
     * @param integer $priority the priority level of the message
     */
    protected function sendResponse($target, $response, $priority = SMARTIRC_MEDIUM)
    {
        if (substr($target, 0, 1) != '#') {
            $type = SMARTIRC_TYPE_QUERY;
        } else {
            $type = SMARTIRC_TYPE_CHANNEL;
        }
        $this->irc->message($type, $target, $response, $priority);
    }

    protected function isAuthenticated($data)
    {
        if (!$this->bot->isAuthenticated($data)) {
            $this->sendResponse($data->nick, 'Error: You need to be authenticated to run this command.');
            return false;
        }
        return true;
    }

    /**
     * Register commands.
     * All public final methods are registered. the method name is the prefix of the command
     *
     * @param Net_SmartIRC $irc
     */
    public function register(Net_SmartIRC $irc)
    {
        // register timer to handle events from database
        $irc->registerTimehandler(3000, $this, 'notifyEvents');

        // register all commands
        $methods = $this->getMethods();
        foreach ($methods as $methodName => $method) {
            $commandName = $this->getCommandName($methodName);
            $regex = "^!?{$commandName}\b";
            $irc->registerActionhandler(SMARTIRC_TYPE_QUERY, $regex, $this, $methodName);
        }
    }

    /**
     * Get public final methods that will be used as bot commands
     *
     * @return ReflectionMethod[]
     */
    private function getMethods()
    {
        $methods = array();
        $reflectionClass = new ReflectionClass($this);
        foreach ($reflectionClass->getMethods() as $method) {
            if (
                $method->isPublic() // only public
                && !$method->isStatic() // no static
                && substr($method->getName(), 0, 2) != '__' // no magic
                && $method->isFinal() // must be final
            ) {
                $methods[$method->getName()] = $method;
            }
        }

        return $methods;
    }

    /**
     * Transform camel case name to dash version
     *
     * @param  string $name
     * @return string
     */
    private function getCommandName($name)
    {
        return strtolower(preg_replace('/([^A-Z-])([A-Z])/', '$1-$2', $name));
    }
}
