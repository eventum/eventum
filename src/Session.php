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

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

/**
 * Wrapper class for sessions. This is an initial bare bones implementation.
 * Additional methods will be later as needed.
 */
class Session
{
    /**
     * Sets the passed variable in the session using the specified name.
     *
     * @param   string $name Name to store variable under.
     * @param   mixed $var Variable to store in session.
     */
    public static function set($name, $var)
    {
        static::getInstance()->set($name, $var);
    }

    /**
     * Returns the session variable specified by $name
     *
     * @param   string $name The name of variable to be returned.
     * @param   mixed $default What should be returned if the named variabe is not set
     * @return  mixed The session variable.
     */
    public static function get($name, $default = null)
    {
        static::getInstance()->get($name, $default);
    }

    /**
     * Initialize the session with $usr_id
     *
     * @param   integer $usr_id The ID of the user
     */
    public static function init($usr_id)
    {
        $session = static::getInstance();

        // set the IP in the session so we can check it later
        $session->set('login_ip', $_SERVER['REMOTE_ADDR']);

        // store user ID in session
        // XXX: Should we perform checks on this usr ID before accepting it?
        $session->set('usr_id', $usr_id);
    }

    private static function getInstance()
    {
        static $session;

        if (!$session) {
            $session = new SymfonySession();
            $session->start();
        }

        return $session;
    }
}
