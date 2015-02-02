<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014 Eventum Team.                                     |
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
// | 51 Franklin Street, Suite 330                                          |
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
            $protected = isset($tags['access']) && $tags['access'][0][0] == 'protected';
            $signature = $this->getSignature($tags, $protected);
            $function = $this->getFunctionDecorator($method, $protected);
            $signatures[$methodName] = array(
                'function'  => $function,
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
     * @param bool $protected true if method should be protected with username/password
     * @return array
     */
    private function getSignature($tags, $protected)
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
        if ($protected && !isset($_SERVER['PHP_AUTH_USER'])) {
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
     * @return Exception
     * @throws Exception
     */
    private function getXmlRpcType($type)
    {
        if ($type == 'integer') {
            $type = 'int';
        } elseif ($type == 'bool') {
            $type = 'boolean';
        }

        return $type;
    }

    /**
     * Create callable to proxy
     *
     * @param ReflectionMethod $method
     * @param bool $protected true if method should be protected with username/password
     * @return callable
     */
    private function getFunctionDecorator($method, $protected)
    {
        // create $handler variable for PHP 5.3
        $handler = $this;

        $function = function ($params) use ($handler, $method, $protected) {
            return $handler->handle($method, $params, $protected);
        };

        return $function;
    }

    /**
     * NOTE: this needs to be public for PHP 5.3 compatibility
     *
     * @param ReflectionMethod $method
     * @param XML_RPC_Message $message
     * @param bool $protected true if method should be protected with username/password
     * @return string
     */
    public function handle($method, $message, $protected)
    {
        $params = array();
        $nparams = $message->getNumParams();
        for ($i = 0; $i < $nparams; $i++) {
            $params[] = XML_RPC_decode($message->getParam($i));
        }

        // there's method to set this via $client->setAutoBase64(true);
        // but nothing at server side. where we actually need it
        $GLOBALS['XML_RPC_auto_base64'] = true;

        try {
            if ($protected) {
                list($email, $password) = $this->getAuthParams($params);

                $usr_id = User::getUserIDByEmail($email, true);
                // FIXME: The role check shouldn't be hardcoded for project 1
                $prj_id = 1;
                if (!Auth::isCorrectPassword($email, $password)
                    || (User::getRoleByUser($usr_id, $prj_id) <= User::getRoleID('Customer'))
                ) {
                    throw new RemoteApiException(
                        "Authentication failed for $email.\nYour email/password is invalid or you do not have the proper role."
                    );
                }

                RemoteApi::createFakeCookie($email);
            }

            $res = $method->invokeArgs($this->api, $params);
        } catch (Exception $e) {
            global $XML_RPC_erruser;
            $res = new XML_RPC_Response(0, $XML_RPC_erruser + 1, $e->getMessage());
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
    private function getAuthParams(&$params) {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            return array($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        }

        return array_splice($params, 0, 2);
    }
}
