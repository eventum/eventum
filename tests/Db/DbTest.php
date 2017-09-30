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

namespace Eventum\Test\Db;

use DB_Helper;
use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Test\TestCase;

/**
 * Test DB layer to work as expected
 *
 * @group db
 */
class DbTest extends TestCase
{
    /**
     * @var AdapterInterface
     */
    private $db;

    public function setUp()
    {
        $this->db = DB_Helper::getInstance(false);
    }

    /**
     * @dataProvider quoteData
     */
    public function testQuote($input, $exp)
    {
        $res = $this->db->escapeSimple($input);
        $this->assertEquals($exp, $res);
    }

    public function quoteData()
    {
        return [
            ["C'est La Vie", "C\\'est La Vie"],
            [['Jää-äär'], null],
        ];
    }

    /** @group query */
    public function testQuery()
    {
        $res = $this->db->query('update `user` set usr_lang=? where 1=0', ['en_US']);
        $this->assertEquals(true, $res);
    }

    /** @group getAll */
    public function testGetAllDefault()
    {
        $res = $this->db->getAll(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM `user` WHERE usr_id<=?', [2],
            AdapterInterface::DB_FETCHMODE_DEFAULT
        );
        $this->assertInternalType('array', $res);
        $exp = [
            0 => [
                0 => 1,
                1 => 'system',
                2 => 'system-account@example.com',
                3 => null,
            ],
            1 => [
                0 => 2,
                1 => 'Admin User',
                2 => 'admin@example.com',
                3 => null,
            ],
        ];
        $this->assertEquals($exp, $res);
    }

    /** @group getAll */
    public function testGetAllAssoc()
    {
        $res = $this->db->getAll(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM `user` WHERE usr_id<=? AND usr_id!=42', [2],
            AdapterInterface::DB_FETCHMODE_ASSOC
        );
        $this->assertInternalType('array', $res);
        $exp = [
            0 => [
                'usr_id' => 1,
                'usr_full_name' => 'system',
                'usr_email' => 'system-account@example.com',
                'usr_lang' => '',
            ],
            1 => [
                'usr_id' => 2,
                'usr_full_name' => 'Admin User',
                'usr_email' => 'admin@example.com',
                'usr_lang' => null,
            ],
        ];
        $this->assertEquals($exp, $res);
    }

    /** @group fetchAssoc */
    public function testFetchAssocDefault()
    {
        $res = $this->db->fetchAssoc(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM `user` WHERE usr_id<=?',
            [2],
            AdapterInterface::DB_FETCHMODE_DEFAULT
        );

        $this->assertInternalType('array', $res);
        $exp = [
            1 => [
                0 => 'system',
                1 => 'system-account@example.com',
                2 => null,
            ],
            2 => [
                0 => 'Admin User',
                1 => 'admin@example.com',
                2 => null,
            ],
        ];
        $this->assertEquals($exp, $res);
    }

    /** @group fetchAssoc */
    public function testFetchAssocAssoc()
    {
        $res = $this->db->fetchAssoc(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM `user` WHERE usr_id<=?',
            [2],
            AdapterInterface::DB_FETCHMODE_ASSOC
        );

        $this->assertInternalType('array', $res);
        $exp = [
            1 => [
                'usr_full_name' => 'system',
                'usr_email' => 'system-account@example.com',
                'usr_lang' => null,
            ],
            2 => [
                'usr_full_name' => 'Admin User',
                'usr_email' => 'admin@example.com',
                'usr_lang' => null,
            ],
        ];
        $this->assertEquals($exp, $res);
    }

    /**
     * fetchAssoc with tow columns behaves differently with Eventum\Db\DbPear.
     * you should really use fetchpair then
     *
     * @group fetchAssoc
     */
    public function testFetchAssoc()
    {
        $stmt = 'SELECT sta_id, sta_title FROM `status` ORDER BY sta_rank ASC';
        $res = $this->db->getPair($stmt);
        $exp = [
            1 => 'discovery',
            2 => 'requirements',
            3 => 'implementation',
            4 => 'evaluation and testing',
            5 => 'released',
            6 => 'killed',
        ];
        $this->assertEquals($exp, $res);
    }

    /** @group getColumn */
    public function testGetColumn()
    {
        $res = $this->db->getColumn(
            'SELECT usr_full_name FROM `user` WHERE usr_id<=?',
            [2]
        );

        $this->assertInternalType('array', $res);
        $exp = [
            0 => 'system',
            1 => 'Admin User',
        ];
        $this->assertEquals($exp, $res);
    }

    /** @group getOne */
    public function testGetOne()
    {
        $res = $this->db->getOne(
            'SELECT usr_id FROM `user` WHERE usr_email=?', ['nosuchemail@.-']
        );
        $this->assertNull($res);

        $res = $this->db->getOne(
            'SELECT usr_id FROM `user` WHERE usr_email=?', ['admin@example.com']
        );
        $this->assertEquals(2, $res);
    }

    /** @group getPair */
    public function testGetPair()
    {
        $res = $this->db->getPair(
            'SELECT usr_id,usr_full_name FROM `user` WHERE usr_email=?', ['nosuchemail@.-']
        );
        $this->assertInternalType('array', $res);
        $this->assertEmpty($res);

        $res = $this->db->getPair(
            'SELECT usr_id,usr_full_name FROM `user` WHERE usr_id<=2'
        );
        $this->assertInternalType('array', $res);
        $exp = [1 => 'system', 2 => 'Admin User'];
        $this->assertEquals($exp, $res);
    }

    /** @group getRow */
    public function testGetRowDefault()
    {
        $res = $this->db->getRow(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM `user` WHERE usr_id<=?',
            [2], AdapterInterface::DB_FETCHMODE_DEFAULT
        );

        $this->assertInternalType('array', $res);
        $exp = [
            0 => '1',
            1 => 'system',
            2 => 'system-account@example.com',
            3 => null,
        ];
        $this->assertEquals($exp, $res);
    }

    /** @group getRow */
    public function testGetRowAssoc()
    {
        $res = $this->db->getRow(
            'SELECT usr_id,usr_full_name,usr_email,usr_lang FROM `user` WHERE usr_id<=?',
            [2], AdapterInterface::DB_FETCHMODE_ASSOC
        );

        $this->assertInternalType('array', $res);
        $exp = [
            'usr_id' => '1',
            'usr_full_name' => 'system',
            'usr_email' => 'system-account@example.com',
            'usr_lang' => null,
        ];
        $this->assertEquals($exp, $res);
    }

    public function testBuildSet()
    {
        $table = 'test_' . __FUNCTION__;
        $this->db->query("CREATE TEMPORARY TABLE $table (id INT, v1 CHAR(1), v2 CHAR(2))");

        $params = [
            'id' => 1,
            'v1' => 'a',
            'v2' => '22',
        ];
        $stmt = "INSERT INTO $table SET " . DB_Helper::buildSet($params);

        DB_Helper::getInstance()->query($stmt, $params);
    }
}
