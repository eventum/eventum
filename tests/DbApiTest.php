<?php

class DbApiTest extends TestCase
{
    public function testPearApi()
    {
        $this->assertDatabase();

        $config = DB_Helper::getConfig();
        $instance = new DbPear($config);
        $this->assertNotNull($instance);
    }

    public function testNullApi()
    {
        $config = DB_Helper::getConfig();
        $instance = new DbNull($config);
        $this->assertNotNull($instance);
    }
}
