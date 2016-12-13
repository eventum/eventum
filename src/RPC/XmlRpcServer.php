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

namespace Eventum\RPC;

use APIAuthToken;
use Auth;
use AuthCookie;
use Exception;
use PhpXmlRpc;
use ReflectionClass;
use ReflectionMethod;

class XmlRpcServer
{
    /** @var RemoteApi */
    protected $api;

    /** @var \ReflectionClass */
    protected $reflectionClass;

    /** @var PhpXmlRpc\Server */
    protected $server;

    /** @var PhpXmlRpc\Encoder */
    protected $encoder;

    public function __construct($api)
    {
        $this->api = $api;
        $this->reflectionClass = new ReflectionClass($this->api);
        $this->encoder = new PhpXmlRpc\Encoder();

        $services = $this->getXmlRpcMethodSignatures();
        $this->server = new PhpXmlRpc\Server($services);
    }

    /**
     * Get XMLRPC method signatures
     *
     * @return array
     */
    private function getXmlRpcMethodSignatures()
    {
        $signatures = [];
        foreach ($this->getMethods() as $methodName => $method) {
            $tags = $this->parseBlockComment($method->getDocComment());
            $public = isset($tags['access']) && $tags['access'][0][0] == 'public';
            $signature = $this->getSignature($tags, $public);
            $pdesc = isset($tags['param']) ? $tags['param'] : null;
            $function = $this->getFunctionDecorator($method, $public, $pdesc);
            $signatures[$methodName] = [
                'function' => $function,
                'signature' => [$signature],
                'docstring' => $method->getDocComment(),
            ];
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
        $methods = [];
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

        $tags = [];
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
                $tags[$name] = [];
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
        $signature = [];

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
        $function = function ($message) use ($method, $public, $pdesc) {
            /** @var PhpXmlRpc\Request $message */
            $params = [];
            $n = $message->getNumParams();
            for ($i = 0; $i < $n; $i++) {
                $params[] = $this->encoder->decode($message->getParam($i));
            }

            return $this->handle($method, $params, $public, $pdesc);
        };

        return $function;
    }

    /**
     * @param ReflectionMethod $method
     * @param array $params Method parameters in already decoded into PHP types
     * @param bool $public true if method should not be protected with login/password
     * @param array $pdesc Parameter descriptions
     * @return string
     */
    private function handle($method, $params, $public, $pdesc)
    {
        try {
            if (!$public) {
                list($email, $password) = $this->getAuthParams($params);

                if (!Auth::isCorrectPassword($email, $password)
                    && !APIAuthToken::isTokenValidForEmail(
                        $password, $email
                    )
                ) {
                    // FIXME: role is not checked here
                    throw new RemoteApiException(
                        "Authentication failed for $email. Your login/password/api key is invalid or you do not have the proper role."
                    );
                }

                AuthCookie::setAuthCookie($email);
            }

            if ($pdesc) {
                $this->decodeParams($params, $pdesc);
            }

            $res = $method->invokeArgs($this->api, $params);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 1;
            $code += PhpXmlRpc\PhpXmlRpc::$xmlrpcerruser;

            $res = new PhpXmlRpc\Response(0, $code, $e->getMessage());
        }

        if (!$res instanceof PhpXmlRpc\Response) {
            $res = new PhpXmlRpc\Response($this->encoder->encode($res));
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
            return [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']];
        }

        return array_splice($params, 0, 2);
    }
}
