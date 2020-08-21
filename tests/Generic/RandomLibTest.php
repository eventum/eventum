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

namespace Eventum\Test\Generic;

use Eventum\Test\TestCase;
use Misc;

class RandomLibTest extends TestCase
{
    public function testRandom(): void
    {
        $res = Misc::generateRandom(32);
        $this->assertNotNull($res);
        $this->assertEquals(32, Misc::countBytes($res));
    }

    public function testPasswordGen(): void
    {
        $hash = md5(Misc::generateRandom(32));
        $password = substr($hash, 0, 12);
        $this->assertEquals(12, Misc::countBytes($password));
    }
}
