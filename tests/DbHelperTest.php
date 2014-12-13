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
}
