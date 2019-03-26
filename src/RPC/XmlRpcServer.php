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
use DateTime;
use Eventum\Monolog\Logger;
use Exception;
use Misc;
use PhpXmlRpc;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;

class XmlRpcServer
{
    /**
     * Classes allowed to be unserialized
     *
     * @var string[]
     */
    private const SERIALIZE_ALLOWED_CLASSES = [
        DateTime::class,
    ];

    /** @var RemoteApi */
    protected $api;

    /** @var \ReflectionClass */
    protected $reflectionClass;

    /** @var PhpXmlRpc\Server */
    protected $server;

    /** @var PhpXmlRpc\Encoder */
    protected $encoder;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(RemoteApi $api)
    {
        $this->api = $api;
        $this->reflectionClass = new ReflectionClass($this->api);
        $this->encoder = new PhpXmlRpc\Encoder();
        $this->logger = Logger::cli();

        $services = $this->getXmlRpcMethodSignatures();
        $this->server = new PhpXmlRpc\Server($services, false);
    }

    public function run($data = null): void
    {
        $this->server->service($data);
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
            $public = isset($tags['access']) && $tags['access'][0][0] === 'public';
            $signature = $this->getSignature($tags, $public);
            $pdesc = $tags['param'] ?? null;
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
                && strpos($method->getName(), '__') !== 0 // no magic
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

            if (empty($line) || $line[0] !== '@') {
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
    private function decodeParams(&$params, $description): void
    {
        foreach ($params as $i => &$param) {
            $type = $description[$i][0];
            $has_type = $this->getXmlRpcType($type, null);
            // if there is no internal type, and type exists as class, unserialize it
            if (!$has_type && in_array($type, self::SERIALIZE_ALLOWED_CLASSES, true)) {
                $param = Misc::unserialize($param, self::SERIALIZE_ALLOWED_CLASSES);
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

            if ($pdesc) {
                $this->decodeParams($params, $pdesc);
            }

            return $this->handle($method, $params, $public);
        };

        return $function;
    }

    /**
     * @param ReflectionMethod $method
     * @param array $params Method parameters in already decoded into PHP types
     * @param bool $public true if method should not be protected with login/password
     * @return string
     */
    private function handle($method, $params, $public)
    {
        try {
            $email = null;
            if (!$public) {
                [$email, $password] = $this->getAuthParams($params);

                // FIXME: role is not checked here
                if (!$this->isValidLogin($email, $password)) {
                    throw RemoteApiException::authenticationFailed($email);
                }

                AuthCookie::setAuthCookie($email);
            }

            $res = $method->invokeArgs($this->api, $params);
            $this->logRequest($method->name, ['params' => $params, 'email' => $email]);
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

    private function isValidLogin(string $email, string $password): bool
    {
        return APIAuthToken::isTokenValidForEmail($password, $email) || Auth::isCorrectPassword($email, $password);
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

    /**
     * log info about request
     *
     * @param string $message
     * @param array $context
     */
    private function logRequest($message, $context): void
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $context['agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        $this->logger->info($message, $context);
    }
}
