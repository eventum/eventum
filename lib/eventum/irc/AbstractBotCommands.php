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
     * @param int $priority the priority level of the message
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

    /**
     * @param Net_SmartIRC_data $data
     */
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
        $irc->registerTimeHandler(3000, $this, 'notifyEvents');

        // register all commands
        $methods = $this->getMethods();
        foreach ($methods as $methodName => $method) {
            $commandName = $this->getCommandName($methodName);
            $regex = "^!?{$commandName}\b";
            $irc->registerActionHandler(SMARTIRC_TYPE_QUERY, $regex, $this, $methodName);
        }
    }

    /**
     * Get public final methods that will be used as bot commands
     *
     * @return ReflectionMethod[]
     */
    private function getMethods()
    {
        $methods = [];
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
