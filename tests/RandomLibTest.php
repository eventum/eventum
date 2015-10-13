<?php

class RandomLibTest extends PHPUnit_Framework_TestCase
{
    public function testRandom()
    {
        $res = Misc::generateRandom(32);
        $this->assertNotNull($res);
        $this->assertEquals(32, Misc::countBytes($res));
    }
}
