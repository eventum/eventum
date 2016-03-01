<?php

use Eventum\Db\Adapter\NullAdapter;
use Eventum\Db\Adapter\PearAdapter;

class DbApiTest extends TestCase
{
    public function testPearApi()
    {
        $this->assertDatabase();

        $config = DB_Helper::getConfig();
        $instance = new PearAdapter($config);
        $this->assertNotNull($instance);
    }

    public function testNullApi()
    {
        $config = DB_Helper::getConfig();
        $instance = new NullAdapter($config);
        $this->assertNotNull($instance);
    }
}
