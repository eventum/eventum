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

use Session;

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
        self::addMessage($msg, $type = self::MSG_INFO);
    }

    /**
     * Add message of type error
     *
     * @param string $msg
     */
    public function addErrorMessage($msg)
    {
        self::addMessage($msg, $type = self::MSG_ERROR);
    }

    /**
     * Add message of type HTML box
     *
     * @param string $msg
     */
    public function addHtmlBoxMessage($msg)
    {
        self::addMessage($msg, $type = self::MSG_HTML_BOX);
    }

    /**
     * Get messages, clear the session store
     *
     * @return string[]
     */
    public function getMessages()
    {
        $messages = Session::get('messages', []);
        Session::set('messages', []);

        return $messages;
    }

    public function mapMessages($result, $map)
    {
        foreach ($map as $val => $info) {
            if ($result == $val) {
                self::addMessage($info[0], $info[1]);

                return;
            }
        }
    }

    private function addMessage($msg, $type)
    {
        $messages = Session::get('messages', []);
        $messages[] = [
            'text' => $msg,
            'type' => $type,
        ];
        Session::set('messages', $messages);
    }
}
