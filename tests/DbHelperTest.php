<?php

class DbHelperTest extends PHPUnit_Framework_TestCase
{
    public function testBuildSet()
    {
        $params = array(
            'a' => 'b',
            'c' => 'd',
        );

        $params['d'] = 'f';

        $res = DB_Helper::buildSet($params);
        $exp = "a=?, c=?, d=?";
        $this->assertEquals($exp, $res);
    }

    public function testBuildList()
    {
        // simple test
        $ids = array(
            1, 2, 'a', 'f'
        );
        $res = DB_Helper::buildList($ids);
        $exp = "?, ?, ?, ?";
        $this->assertEquals($exp, $res);

        // test in a sql
        $res = "DELETE FROM {{%product}} WHERE pro_id IN (" . DB_Helper::buildList($ids) . ")";
        $exp = "DELETE FROM {{%product}} WHERE pro_id IN (?, ?, ?, ?)";
        $this->assertEquals($exp, $res);

        // test combining params with a list
        $params = array(110);
        $stmt = "psd_prj_id=? AND psd_sta_id IN (" . DB_Helper::buildList($ids) . ")";
        $params = array_merge($params, $ids);
        $res = $stmt . '|' . join(',', $params);
        $exp = 'psd_prj_id=? AND psd_sta_id IN (?, ?, ?, ?)|110,1,2,a,f';
        $this->assertEquals($exp, $res);

        // test merge two arrays
        $stmt = "WHERE icf_fld_id IN (" . DB_Helper::buildList($ids) . ") AND icf_value IN (" . DB_Helper::buildList($ids) . ")";
        $params = array_merge($ids, $ids);
        $res = $stmt . '|' . join(',', $params);
        $exp = 'WHERE icf_fld_id IN (?, ?, ?, ?) AND icf_value IN (?, ?, ?, ?)|1,2,a,f,1,2,a,f';
        $this->assertEquals($exp, $res);
    }
}
