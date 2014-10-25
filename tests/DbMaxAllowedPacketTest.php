<?php

/**
 * Separate test to use different getInstance call
 */
class DbMaxAllowedPacketTest extends PHPUnit_Framework_TestCase {
    public function testGetMaxAllowedPacket() {
        // this should not fail if db is not reachable
        $res = DB_Helper::getMaxAllowedPacket();
        $this->assertNotNull($res);
        $this->assertGreaterThan(0, $res);
    }
}
