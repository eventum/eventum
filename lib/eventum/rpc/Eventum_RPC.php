<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2008 Elan Ruusamäe                                     |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

class Eventum_RPC_Exception extends Exception
{
}

class Eventum_RPC
{
    /**
     * The URL of Eventum installation to send requests to
     *
     * @var string
     */
    private $url;

    /**
     * @var XML_RPC_Client
     */
    private $client;

    public function __construct($url)
    {
        $this->url = $url;
        $this->client = $this->getClient();
    }

    /**
     * Change the current debug mode
     *
     * @param int $debug  where 1 = on, 0 = off
     */
    public function setDebug($debug)
    {
        $this->client->setDebug($debug);
    }

    /**
     * Set username and password properties for connecting to the RPC server
     *
     * @param string $username the user name
     * @param string $password the password
     * @see XML_RPC_Client::$username, XML_RPC_Client::$password
     */
    public function setCredentials($username, $password)
    {
        $this->client->setCredentials($username, $password);
    }

    private function getClient()
    {
        $data = parse_url($this->url);
        if (!isset($data['port'])) {
            $data['port'] = $data['scheme'] == 'https' ? 443 : 80;
        }
        if (!isset($data['path'])) {
            $data['path'] = '';
        }
        $data['path'] .= '/rpc/xmlrpc.php';

        return new XML_RPC_Client($data['path'], $data['host'], $data['port']);
    }

    public function __call($method, $args)
    {
        $params = array();
        foreach ($args as $arg) {
            $type = gettype($arg);
            if ($type == 'integer') {
                $type = 'int';
            }
            $params[] = new XML_RPC_Value($arg, $type);
        }
        $msg = new XML_RPC_Message($method, $params);

        $result = $this->client->send($msg);

        if ($result === 0) {
            throw new Eventum_RPC_Exception($this->client->errstr);
        }
        if (is_object($result) && $result->faultCode()) {
            throw new Eventum_RPC_Exception($result->faultString());
        }

        $value = XML_RPC_decode($result->value());

        return $value;
    }
}
