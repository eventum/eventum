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

namespace Eventum\Test;

use Eventum\Auth\PasswordHash;
use Misc;

class PasswordAuthTest extends TestCase
{
    /** @var string */
    private $password = 'Tr0ub4dour&3';

    /** @var array */
    private $hashes = [
        // hash created with password_hash($password, PASSWORD_DEFAULT)
        'password_hash' => '$2y$10$xRKqEPixGvSdeopyfzQACe2Tppb43OljoFfGUBdPTkgtpdvjvCEJO',
    ];

    public function testAuthPassword(): void
    {
        // success
        $res = PasswordHash::verify($this->password, $this->hashes['password_hash']);
        $this->assertTrue($res);

        // failures
        $password = 'meh';
        $res = PasswordHash::verify($password, $this->hashes['password_hash']);
        $this->assertFalse($res);
    }

    public function testPasswordHash(): void
    {
        $hash = password_hash($this->password, PASSWORD_DEFAULT);
        $length = Misc::countBytes($hash);
        $this->assertEquals(60, $length);
    }

    public function testPasswordGetInfo(): void
    {
        $res = password_get_info($this->hashes['password_hash']);
        if (PHP_VERSION_ID >= 70400) {
            // it's "2y" some-why, and PASSWORD_DEFAULT=null
            // maybe bug, maybe implementation change
            $this->assertPasswordInfoArray($res, '2y', 'bcrypt');
            // deal with this later
            $this->markTestSkipped('PHP 7.4 APIs unclear yet');
        } else {
            $this->assertPasswordInfoArray($res, 1, 'bcrypt');
        }
    }

    public function testPasswordNeedsRehash(): void
    {
        $res = password_needs_rehash($this->hashes['password_hash'], PASSWORD_DEFAULT);
        $this->assertFalse($res);
    }

    private function assertPasswordInfoArray($res, $algo = 1, $algoName = 'unknown'): void
    {
        $this->assertIsArray($res);
        $this->assertArrayHasKey('algo', $res);
        $this->assertInternalType(gettype($algo), $res['algo']);
        $this->assertEquals($algo, $res['algo']);

        $this->assertArrayHasKey('algoName', $res);
        $this->assertIsString($res['algoName']);
        $this->assertEquals($algoName, $res['algoName']);

        $this->assertArrayHasKey('options', $res);
        $this->assertIsArray($res['options']);
    }
}
