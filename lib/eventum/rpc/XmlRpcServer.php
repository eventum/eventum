<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014-2015 Eventum Team.                                |
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

class XmlRpcServer
{
    /**
     * @var RemoteApi
     */
    protected $api;

    /**
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    public function __construct($api)
    {
        $this->api = $api;
        $this->reflectionClass = new ReflectionClass($this->api);

        $services = $this->getXmlRpcMethodSignatures();
        $server = new XML_RPC_Server($services);
        $this->server = $server;
    }

    /**
     * Get XMLRPC method signatures
     *
     * @return array
     */
    private function getXmlRpcMethodSignatures()
    {
        $signatures = array();
        foreach ($this->getMethods() as $methodName => $method) {
            $tags = $this->parseBlockComment($method->getDocComment());
            $public = isset($tags['access']) && $tags['access'][0][0] == 'public';
            $signature = $this->getSignature($tags, $public);
            $pdesc = isset($tags['param']) ? $tags['param'] : null;
            $function = $this->getFunctionDecorator($method, $public, $pdesc);
            $signatures[$methodName] = array(
                'function' => $function,
                'signature' => array($signature),
                'docstring' => $method->getDocComment(),
            );
        }

        return $signatures;
    }

    /**
     * Get public methods to be exposed over API
     *
     * @return ReflectionMethod[]
     */
    private function getMethods()
    {
        $methods = array();
        foreach ($this->reflectionClass->getMethods() as $method) {
            if (
                $method->isPublic() // only public
                && !$method->isStatic() // no static
                && substr($method->getName(), 0, 2) != '__' // no magic
            ) {
                $methods[$method->getName()] = $method;
            }
        }

        return $methods;
    }

    /**
     * Parse PHP Doc block and return array for each tag
     *
     * @param string $doc
     * @return array
     */
    private function parseBlockComment($doc)
    {
        $doc = preg_replace('#/+|\t+|\*+#', '', $doc);

        $tags = array();
        foreach (explode("\n", $doc) as $line) {
            $line = trim($line);
            $line = preg_replace('/\s+/', ' ', $line);

            if (empty($line) || $line[0] != '@') {
                continue;
            }

            $tokens = explode(' ', $line);
            if (empty($tokens)) {
                continue;
            }

            $name = str_replace('@', '', array_shift($tokens));

            if (!isset($tags[$name])) {
                $tags[$name] = array();
            }
            $tags[$name][] = $tokens;
        }

        return $tags;
    }

    /**
     * Extract parameter types for XMLRPC from PHP docBlock
     *
     * @param array $tags
     * @param bool $public true if method not should be protected with username/password
     * @return array
     */
    private function getSignature($tags, $public)
    {
        $signature = array();

        // first goes return type
        if (isset($tags['return'])) {
            $return = $tags['return'][0][0];
        } else {
            $return = 'string';
        }
        $signature[] = $this->getXmlRpcType($return);

        // for protected add email and password strings
        // skip adding if HTTP Authorization header is present
        if (!$public && !isset($_SERVER['PHP_AUTH_USER'])) {
            $signature[] = $this->getXmlRpcType('string');
            $signature[] = $this->getXmlRpcType('string');
        }

        // now the rest of the parameters
        if (isset($tags['param'])) {
            foreach ($tags['param'] as $param) {
                $signature[] = $this->getXmlRpcType($param[0]);
            }
        }

        return $signature;
    }

    /**
     * Get type for XMLRPC.
     *
     * @param string $type
     * @param string $default_type
     * @return string
     */
    private function getXmlRpcType($type, $default_type = 'string')
    {
        switch ($type) {
            case 'integer':
            case 'int':
                return 'int';

            case 'bool':
            case 'boolean':
                return 'boolean';

            case 'string':
            case 'array':
            case 'base64':
            case 'struct':
                return $type;
        }

        return $default_type;
    }

    /**
     * Decode parameters.
     * Parameters that are objects are encoded via php serialize() method
     *
     * @param array $params actual parameters
     * @param array $description parameter descriptions
     */
    private function decodeParams(&$params, $description)
    {
        foreach ($params as $i => &$param) {
            $type = $description[$i][0];
            $has_type = $this->getXmlRpcType($type, null);
            // if there is no internal type, and type exists as class, unserialize it
            if (!$has_type && class_exists($type)) {
                $param = unserialize($param);
            }
        }
    }

    /**
     * Create callable to proxy
     *
     * @param ReflectionMethod $method
     * @param bool $public true if method should not be protected with username/password
     * @param array $pdesc Parameter descriptions
     * @return callable
     */
    private function getFunctionDecorator($method, $public, $pdesc)
    {
        // create $handler variable for PHP 5.3
        $handler = $this;

        $function = function ($message) use ($handler, $method, $public, $pdesc) {
            /** @var XML_RPC_Message $message */
            $params = array();
            $n = $message->getNumParams();
            for ($i = 0; $i < $n; $i++) {
                $params[] = XML_RPC_decode($message->getParam($i));
            }

            return $handler->handle($method, $params, $public, $pdesc);
        };

        return $function;
    }

    /**
     * NOTE: this needs to be public for PHP 5.3 compatibility
     *
     * @param ReflectionMethod $method
     * @param array $params Method parameters in already decoded into PHP types
     * @param bool $public true if method should not be protected with login/password
     * @param array $pdesc Parameter descriptions
     * @return string
     */
    public function handle($method, $params, $public, $pdesc)
    {
        // there's method to set this via $client->setAutoBase64(true);
        // but nothing at server side. where we actually need it
        $GLOBALS['XML_RPC_auto_base64'] = true;

        try {
            if (!$public) {
                list($email, $password) = $this->getAuthParams($params);

                if (!Auth::isCorrectPassword($email, $password)) {
                    // FIXME: role is not checked here
                    throw new RemoteApiException(
                        "Authentication failed for $email. Your login/password is invalid or you do not have the proper role."
                    );
                }

                AuthCookie::setAuthCookie($email);
            }

            if ($pdesc) {
                $this->decodeParams($params, $pdesc);
            }

            $res = $method->invokeArgs($this->api, $params);
        } catch (Exception $e) {
            global $XML_RPC_erruser;
            $code = $e->getCode() ?: 1;
            $res = new XML_RPC_Response(0, $XML_RPC_erruser + $code, $e->getMessage());
        }

        if (!$res instanceof XML_RPC_Response) {
            $res = new XML_RPC_Response(XML_RPC_Encode($res));
        }

        return $res;
    }

    /**
     * Get auth username and password.
     * Take credentials from HTTP Authorization, otherwise chop off from parameters.
     *
     * @param array $params
     * @return array
     */
    private function getAuthParams(&$params)
    {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            return array($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        }

        return array_splice($params, 0, 2);
    }
}
