<?php

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $config = Setup::get();

        $config['item1'] = 'one';
        $this->assertEquals('one', $config['item1'], "config as array set works");
        $config->item2 = 'two';
        $this->assertEquals('two', $config['item2'], "config as object set works");

        $this->assertNull($config->nokey, "accessing keys that don't exist is fine");
        $this->assertNull($config['nokey'], "accessing keys that don't exist is fine with arrays too");

        // these will fail if 'smtp' key is missing
        // "Indirect modification of overloaded property" is the error
        //$config->smtp->host = 'localhost';
        //$config['smtp']['host'] = 'localhost';

        $this->assertNull($config['noentry']['host'], 'Can access inaccessible parent as array');
        //$this->assertNull($config->noentry->host, 'Can not access inaccessible parent as object');

        $this->assertTrue(empty($config->noentry->host), "can do empty checks on inaccessible parents");
        $this->assertFalse(isset($config->noentry->host), "can do isset checks on inaccessible parents");
        $this->assertTrue(empty($config['noentry']['host']), "can do empty checks on inaccessible parents");
        $this->assertFalse(isset($config['noentry']['host']), "can do isset checks on inaccessible parents");

        // this avoids the "indirect" error
        $tmp = $config->group;
        $tmp['param3'] = 'value';
        $config->group = $tmp;
        $this->assertEquals('value', $config->group->param3);

        // but this is better:
        // set multilevel entries works
        $array['smtp']['host'] = 'localhost';
        Setup::set($array);
        $this->assertEquals(300, $config->issue_lock, "the other entries are not lost");
        $this->assertEquals('localhost', $config->smtp->host, "config change is present");

        $config = Setup::get();
        $this->assertEquals('one', $config->item1, "config change is present");
    }

    /**
     * @see Mail_Helper::getSMTPSettings does this weird settype:
     * settype($config['smtp']['auth'], 'boolean');
     * that does not work (Indirect modification error),
     * so test version that works
     */
    public function testSetType()
    {
        $config = Setup::get();

        $config['smtp'] = array(
            'from' => 'admin@example.org',
            'host' => 'localhost',
            'port' => '25',
            'auth' => '0',
        );

        $this->assertSame('0', $config['smtp']['auth']);

        $config['smtp']['auth'] = (bool)$config['smtp']['auth'];
        $this->assertFalse($config['smtp']['auth']);
    }

    public function testArrayEmpty()
    {
        $setup = Setup::get();

        // init
        $setup['email_reminder'] = array();

        // pre-requirements
        $this->assertNull($setup['email_reminder']['status']);
        $this->assertNull($setup['email_reminder']['addresses']);

        // check that this is false and does not trigger errors/notices
        $this->assertFalse($setup['email_reminder']['status'] == 'enabled' && $setup['email_reminder']['addresses']);

        $setup['email_reminder'] = array(
            'status' => 'enabled',
            'aadresses' => array(),
        );
        // that empty addresses list is also false
        $this->assertFalse($setup['email_reminder']['status'] == 'enabled' && $setup['email_reminder']['addresses']);
    }
}
