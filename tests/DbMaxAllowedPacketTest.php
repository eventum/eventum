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

/**
 * Separate test to use different getInstance call
 */
class DbMaxAllowedPacketTest extends TestCase
{
    public function setUp()
    {
        $this->assertDatabase();

        if (PHP_VERSION_ID >= 50600) {
            $this->markTestSkipped('PEAR::DB not compatible with php 5.6');
        }
    }

    public function testGetMaxAllowedPacket()
    {
        // this should not fail if db is not reachable
        $res = DB_Helper::getMaxAllowedPacket();
        $this->assertNotNull($res);
        $this->assertGreaterThan(0, $res);
    }
}
