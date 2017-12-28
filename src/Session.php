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

namespace Eventum;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

/**
 * Wrapper class for sessions. This is an initial bare bones implementation.
 * Additional methods will be later as needed.
 */
class Session
{
    /**
     * Sets the passed variable in the session using the specified name.
     *
     * @param   string $name name to store variable under
     * @param   mixed $var variable to store in session
     */
    public static function set($name, $var)
    {
        static::getInstance()->set($name, $var);
    }

    /**
     * Returns the session variable specified by $name
     *
     * @param   string $name the name of variable to be returned
     * @param   mixed $default What should be returned if the named variabe is not set
     * @return  mixed the session variable
     */
    public static function get($name, $default = null)
    {
        return static::getInstance()->get($name, $default);
    }

    /**
     * Initialize the session with $usr_id
     *
     * @param   int $usr_id The ID of the user
     */
    public static function init($usr_id)
    {
        $session = static::getInstance();

        // store user ID in session
        $session->set('usr_id', $usr_id);
    }

    /**
     * Gets the flashbag interface.
     *
     * @return FlashBagInterface
     */
    public static function getFlashBag()
    {
        return static::getInstance()->getFlashBag();
    }

    private static function getInstance()
    {
        static $session;

        if (!$session) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // use PhpBridge so any libraries using native session handling such as CAS Authentication will still work
            $session = new SymfonySession(new PhpBridgeSessionStorage());
            $session->start();
        }

        return $session;
    }
}
