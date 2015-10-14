<?php

class RandomLibTest extends PHPUnit_Framework_TestCase
{
    public function testRandom()
    {
        $res = Misc::generateRandom(32);
        $this->assertNotNull($res);
        $this->assertEquals(32, Misc::countBytes($res));
    }

    public function testPasswordGen() {
        $hash = md5(Misc::generateRandom(32));
        $password = substr($hash, 0, 12);
        $this->assertEquals(12, Misc::countBytes($password));
    }
}
