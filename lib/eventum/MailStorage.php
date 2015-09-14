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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Class MailStorage
 */
class MailStorage
{
    /** @var \Zend\Mail\Storage\AbstractStorage */
    private $storage;

    public function __construct($options)
    {
        $params = $this->convertParams($options);

        $class = $params['storage_class'];
        $this->storage = new $class($params);
    }

    /**
     * Convert parameters from Eventum to Zend syntax.
     *
     * Eventum params:
     *  'ema_hostname'
     *  'ema_port'
     *  'ema_type'
     *  'ema_folder'
     *  'ema_username'
     *  'ema_password'
     */
    private function convertParams($params)
    {
        // Simple options
        $res = array(
            'host' => $params['ema_hostname'],
            'port' => $params['ema_port'],
            'user' => $params['ema_username'],
            'password' => $params['ema_password'],
            'folder' => $params['ema_folder'],
        );

        /**
         * Parse type:
         * - imap
         * - imap/ssl
         * - imap/ssl/novalidate-cert
         * - imap/notls
         * - imap/tls
         * - imap/tls/novalidate-cert
         * - pop3
         * - pop3/ssl
         * - pop3/ssl/novalidate-cert
         * - pop3/notls
         * - pop3/tls
         * - pop3/tls/novalidate-cert
         */
        $type = explode('/', $params['ema_type']);

        $res['storage_class'] = '\\Zend\\Mail\\Storage\\' . ucfirst($type[0]);
        $res['ssl'] = in_array($type[1], array('ssl', 'tls')) ? $type[1] : false;

        // NOTE: novalidate and notls are not supported

        return $res;
    }
}