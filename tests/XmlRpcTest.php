<?php

class XmlRpcTest extends PHPUnit_Framework_TestCase
{
    const DEBUG = 1;

    private $login = 'admin@example.com';
    private $password = 'admin';

    /** @var XML_RPC_Client */
    private static $client;

    public static function setupBeforeClass()
    {
        $setup = Setup::load();
        if (!isset($setup['tests.xmlrpc_url'])) {
            self::markTestSkipped("tests.xmlrpc_url not set in setup");
        }

        /*
         * 'tests.xmlrpc_url' => 'http://localhost/eventum/rpc/xmlrpc.php',
         */
        $url = $setup['tests.xmlrpc_url'];

        $data = parse_url($url);
        if (!isset($data['port'])) {
            $data['port'] = $data['scheme'] == 'https' ? 443 : 80;
        }
        if (!isset($data['path'])) {
            $data['path'] = '';
        }

        $client = new XML_RPC_Client($data['path'], $data['host'], $data['port']);
        $client->setDebug(self::DEBUG);

        self::$client = $client;
    }

    private static function call($method, $args)
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
        $result = self::$client->send($msg);

        if ($result === 0) {
            throw new Exception(self::$client->errstr);
        }
        if (is_object($result) && $result->faultCode()) {
            throw new Exception($result->faultString());
        }

        $value = XML_RPC_decode($result->value());

        return $value;
    }

    public function testGetClosedAbbreviationAssocList()
    {
        $res = self::call('getClosedAbbreviationAssocList', array($this->login, $this->password, 1));
        $exp = array(
            'REL' => 'released',
            'KIL' => 'killed',
        );
        $this->assertEquals($exp, $res);
    }
}
