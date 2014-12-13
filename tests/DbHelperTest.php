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
        $ids = array(
            1, 2, 'a', 'f'
        );
        $res = DB_Helper::buildList($ids);
        $exp = "?, ?, ?, ?";
        $this->assertEquals($exp, $res);
    }
}
