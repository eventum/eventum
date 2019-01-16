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

use AuthPassword;
use InvalidArgumentException;
use Misc;
use stdClass;

class PasswordAuthTest extends TestCase
{
    /** @var string */
    private $password = 'Tr0ub4dour&3';

    /** @var array */
    private $hashes = [
        // hash created with password_hash($password, PASSWORD_DEFAULT)
        'password_hash' => '$2y$10$xRKqEPixGvSdeopyfzQACe2Tppb43OljoFfGUBdPTkgtpdvjvCEJO',

        // hash created with MD5-64
        'md5-64' => 'YSVYLZgOc2I46esatz1lFw==',

        // hash created with md5
        'md5' => '6125582d980e736238e9eb1ab73d6517',
    ];

    public function testAuthPassword(): void
    {
        // success
        $res = AuthPassword::verify($this->password, $this->hashes['password_hash']);
        $this->assertTrue($res);
        $res = AuthPassword::verify($this->password, $this->hashes['md5-64']);
        $this->assertTrue($res);
        $res = AuthPassword::verify($this->password, $this->hashes['md5']);
        $this->assertTrue($res);

        // failures
        $password = 'meh';
        $res = AuthPassword::verify($password, $this->hashes['password_hash']);
        $this->assertFalse($res);
        $res = AuthPassword::verify($password, $this->hashes['md5-64']);
        $this->assertFalse($res);
        $res = AuthPassword::verify($password, $this->hashes['md5']);
        $this->assertFalse($res);
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider AuthPasswordInvalidArgumentData
     */
    public function testAuthPasswordInvalidArguments($password, $hash): void
    {
        AuthPassword::verify($password, $hash);
    }

    public function AuthPasswordInvalidArgumentData(): array
    {
        return [
            [null, '123'],
            ['a', null],
            ['a', 10],
            [-1, '10'],
            ['', []],
            [[], ''],
            ['', new stdClass()],
            [new stdClass(), ''],
        ];
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

        // these have type "0" aka unknown
        $res = password_get_info($this->hashes['md5-64']);
        $this->assertPasswordInfoArray($res, 0);

        $res = password_get_info($this->hashes['md5']);
        $this->assertPasswordInfoArray($res, 0);
    }

    public function testPasswordNeedsRehash(): void
    {
        $res = password_needs_rehash($this->hashes['password_hash'], PASSWORD_DEFAULT);
        $this->assertFalse($res);

        $res = password_needs_rehash($this->hashes['md5'], PASSWORD_DEFAULT);
        $this->assertTrue($res);

        if (PHP_VERSION_ID < 70400) {
            $this->markTestSkipped('PHP 7.4 APIs unclear yet');
        } else {
            $res = password_needs_rehash($this->hashes['md5-64'], PASSWORD_DEFAULT);
            $this->assertTrue($res);
        }
    }

    private function assertPasswordInfoArray($res, $algo = 1, $algoName = 'unknown'): void
    {
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('algo', $res);
        $this->assertInternalType(gettype($algo), $res['algo']);
        $this->assertEquals($algo, $res['algo']);

        $this->assertArrayHasKey('algoName', $res);
        $this->assertInternalType('string', $res['algoName']);
        $this->assertEquals($algoName, $res['algoName']);

        $this->assertArrayHasKey('options', $res);
        $this->assertInternalType('array', $res['options']);
    }
}
