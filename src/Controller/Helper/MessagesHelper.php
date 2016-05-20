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
namespace Eventum\Controller\Helper;

use Eventum\Session;

class MessagesHelper
{
    const MSG_INFO = 'info';
    const MSG_WARNING = 'warning';
    const MSG_ERROR = 'error';
    const MSG_HTML_BOX = 'html_box';
    const MSG_NOTE_BOX = 'note_box';

    /**
     * Add message of type info
     *
     * @param string $msg
     */
    public function addInfoMessage($msg)
    {
        Session::getFlashBag()->add(self::MSG_INFO, $msg);
    }

    /**
     * Add message of type error
     *
     * @param string $msg
     */
    public function addErrorMessage($msg)
    {
        Session::getFlashBag()->add(self::MSG_ERROR, $msg);
    }

    /**
     * Add message of type HTML box
     *
     * @param string $msg
     */
    public function addHtmlBoxMessage($msg)
    {
        Session::getFlashBag()->add(self::MSG_HTML_BOX, $msg);
    }

    /**
     * Get messages, clear the session store
     *
     * @return string[]
     */
    public function getMessages()
    {
        return Session::getFlashBag()->all();
    }

    public function mapMessages($result, $map)
    {
        $flashBag = Session::getFlashBag();

        foreach ($map as $val => $info) {
            if ($result == $val) {
                $flashBag->add($info[1], $info[0]);

                return;
            }
        }
    }
}
