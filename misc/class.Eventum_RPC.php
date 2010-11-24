<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Defect Tracking System                                     |
// +----------------------------------------------------------------------+
// | Copyright (c) 2008 Elan Ruusamäe                                     |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once 'XML/RPC.php';

class Eventum_RPC_Exception extends Exception {
};

class Eventum_RPC {
    /**
     * A user name for accessing the RPC server
     * @var string
     * @see XML_RPC_Client::setCredentials()
     */
    private $username;
    /**
     * A password for accessing the RPC server
     * @var string
     * @see XML_RPC_Client::setCredentials()
     */
    private $password;

    /**
     * The URL of Eventum installation to send requests to
     * @var string
     */
    private $url;

    /**
     * Set username and password properties for connecting to the RPC server
     *
     * @param string $u  the user name
     * @param string $p  the password
     *
     * @return void
     *
     * @see XML_RPC_Client::$username, XML_RPC_Client::$password
     */
    public function setCredentials($u, $p) {
        $this->username = $u;
        $this->password = $p;
    }

    public function setURL($url) {
        $this->url = $url;
    }

    private $client;
    private $debug = 0;
    private function getClient() {
        if (isset($this->client)) {
            return $this->client;
        }

        $data = parse_url($this->url);
        if (!isset($data['port'])) {
            $data['port'] = $data['scheme'] == 'https' ? 443 : 80;
        }
        if (!isset($data['path'])) {
            $data['path'] = '';
        }
        $data['path'] .= '/rpc/xmlrpc.php';

        $this->client = new XML_RPC_Client($data['path'], $data['host'], $data['port']);
        $this->client->setDebug($this->debug);

        return $this->client;
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    public function __call($method, $args) {
        $params = array();
        $params[] = new XML_RPC_Value($this->username, 'string');
        $params[] = new XML_RPC_Value($this->password, 'string');
        foreach ($args as $arg) {
            $type = gettype($arg);
            if ($type == 'integer') {
                $type = 'int';
            }
            $params[] = new XML_RPC_Value($arg, $type);
        }
        $msg = new XML_RPC_Message($method, $params);

        $client = $this->getClient();
        $result = $client->send($msg);

        if ($result === 0) {
            throw new Eventum_RPC_Exception($client->errstr);
        }
        if (is_object($result) && $result->faultCode()) {
            throw new Eventum_RPC_Exception($result->faultString());
        }

        $details = XML_RPC_decode($result->value());
        foreach ($details as $k => $v) {
            $details[$k] = base64_decode($v);
        }
        return $details;
    }
};
