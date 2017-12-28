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
use Eventum\Test\TestCase;

class DbHelperTest extends TestCase
{
    public function testBuildSet()
    {
        $params = [
            'a' => 'b',
            'c' => 'd',
        ];

        $params['d'] = 'f';

        $res = DB_Helper::buildSet($params);
        $exp = 'a=?, c=?, d=?';
        $this->assertEquals($exp, $res);

        // test combining params with a list
        $stmt = 'SET ' . DB_Helper::buildSet($params) . ' WHERE ID=?';
        $params[] = 11;
        $res = $stmt . '|' . implode(',', $params);
        $exp = 'SET a=?, c=?, d=? WHERE ID=?|b,d,f,11';
        $this->assertEquals($exp, $res);
    }

    public function testBuildList()
    {
        // simple test
        $ids = [
            1, 2, 'a', 'f',
        ];
        $res = DB_Helper::buildList($ids);
        $exp = '?, ?, ?, ?';
        $this->assertEquals($exp, $res);

        // test in a sql
        $res = 'DELETE FROM `product` WHERE pro_id IN (' . DB_Helper::buildList($ids) . ')';
        $exp = 'DELETE FROM `product` WHERE pro_id IN (?, ?, ?, ?)';
        $this->assertEquals($exp, $res);

        // test combining params with a list
        $params = [110];
        $stmt = 'psd_prj_id=? AND psd_sta_id IN (' . DB_Helper::buildList($ids) . ')';
        $params = array_merge($params, $ids);
        $res = $stmt . '|' . implode(',', $params);
        $exp = 'psd_prj_id=? AND psd_sta_id IN (?, ?, ?, ?)|110,1,2,a,f';
        $this->assertEquals($exp, $res);

        // test merge two arrays
        $stmt
            = 'WHERE icf_fld_id IN (' . DB_Helper::buildList($ids) . ') AND icf_value IN (' . DB_Helper::buildList($ids)
            . ')';
        $params = array_merge($ids, $ids);
        $res = $stmt . '|' . implode(',', $params);
        $exp = 'WHERE icf_fld_id IN (?, ?, ?, ?) AND icf_value IN (?, ?, ?, ?)|1,2,a,f,1,2,a,f';
        $this->assertEquals($exp, $res);
    }

    public function testOrderBy()
    {
        $res = DB_Helper::orderBy('ASC');
        $this->assertEquals('ASC', $res);

        $res = DB_Helper::orderBy('desc');
        $this->assertEquals('desc', $res);

        $res = DB_Helper::orderBy('desc having 1=1');
        $this->assertEquals('DESC', $res);

        $res = DB_Helper::orderBy('desc having 1=1', 'asc');
        $this->assertEquals('asc', $res);
    }
}
