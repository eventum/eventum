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

namespace Eventum\Mail;

use Zend\Mail\Protocol;
use Zend\Mail\Storage;

/**
 * Class MailStorage
 */
class MailStorage
{
    /** @var Protocol\Imap|Protocol\Pop3 */
    private $protocol;

    /** @var Storage\Imap|Storage\Pop3 */
    private $storage;

    public function __construct($options)
    {
        $params = $this->convertParams($options);

        /** @var Protocol\Imap|Protocol\Pop3 $class */
        $class = $params['protocol_class'];

        $this->protocol = new $class($params['host'], $params['port'], $params['ssl']);
        $this->protocol->login($params['user'], $params['password']);

        /** @var Storage\Imap|Storage\Pop3 $class */
        $class = $params['storage_class'];
        $this->storage = new $class($this->protocol);
        $this->storage->selectFolder($params['folder']);
    }

    public function countMessages($flags = null)
    {
        return $this->storage->countMessages($flags);
    }

    public function getMails()
    {
        return $this->storage;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    public function getProtocol()
    {
        return $this->protocol;
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
        $res = [
            'host' => $params['ema_hostname'],
            'port' => $params['ema_port'],
            'user' => $params['ema_username'],
            'password' => $params['ema_password'],
            'folder' => $params['ema_folder'],
        ];

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

        $classname = ucfirst($type[0]);
        $res['storage_class'] = '\\Zend\\Mail\\Storage\\' . $classname;
        $res['protocol_class'] = '\\Zend\\Mail\\Protocol\\' . $classname;
        $res['ssl'] = in_array($type[1], ['ssl', 'tls']) ? $type[1] : false;

        // NOTE: novalidate and notls are not supported

        return $res;
    }
}
