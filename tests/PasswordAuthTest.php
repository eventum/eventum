<?php

class PasswordAuthTest extends PHPUnit_Framework_TestCase
{
    public function testPasswordHash()
    {
        $password = "Tr0ub4dour&3";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $length = Misc::countBytes($hash);
        $this->assertEquals(60, $length);
    }

    public function testPasswordGetInfo()
    {
        // hash created with password_hash($password, PASSWORD_DEFAULT)
        $hash = '$2y$10$xRKqEPixGvSdeopyfzQACe2Tppb43OljoFfGUBdPTkgtpdvjvCEJO';
        $res = password_get_info($hash);
        $this->assertPasswordInfoArray($res);
        $this->assertEquals(1, $res['algo']);
        $this->assertEquals('bcrypt', $res['algoName']);

        // these have type "0" aka unknown

        // hash created with MD5-64
        $hash = 'YSVYLZgOc2I46esatz1lFw==';
        $res = password_get_info($hash);
        $this->assertPasswordInfoArray($res);
        $this->assertEquals(0, $res['algo']);

        // hash created with md5
        $hash = '6125582d980e736238e9eb1ab73d6517';
        $res = password_get_info($hash);
        $this->assertPasswordInfoArray($res);
        $this->assertEquals(0, $res['algo']);
    }

    private function assertPasswordInfoArray($res)
    {
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('algo', $res);
        $this->assertInternalType('int', $res['algo']);

        $this->assertArrayHasKey('algoName', $res);
        $this->assertInternalType('string', $res['algoName']);

        $this->assertArrayHasKey('options', $res);
        $this->assertInternalType('array', $res['options']);
    }
}
