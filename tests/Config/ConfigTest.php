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

namespace Eventum\Test\Config;

use Eventum\ServiceContainer;
use Eventum\Test\TestCase;
use Setup;

class ConfigTest extends TestCase
{
    public function testConfig(): void
    {
        $config = ServiceContainer::getConfig();

        $config['item1'] = 'one';
        $this->assertEquals('one', $config['item1'], 'config as array set works');
        $config->item2 = 'two';
        $this->assertEquals('two', $config['item2'], 'config as object set works');

        $this->assertNull($config->nokey, "accessing keys that don't exist is fine");
        $this->assertNull($config['nokey'], "accessing keys that don't exist is fine with arrays too");

        // these will fail if 'smtp' key is missing
        // "Indirect modification of overloaded property" is the error
        //$config->smtp->host = 'localhost';
        //$config['smtp']['host'] = 'localhost';

        if (PHP_VERSION_ID < 70400) {
            // PHP 7.4: Trying to access array offset on value of type null
            // https://bugs.php.net/bug.php?id=37676
            // https://github.com/php/php-src/pull/2031
            $this->assertNull($config['noentry']['host'], 'Can access inaccessible parent as array');
        }
        //$this->assertNull($config->noentry->host, 'Can not access inaccessible parent as object');

        $this->assertTrue(empty($config->noentry->host), 'can do empty checks on inaccessible parents');
        if (PHP_VERSION_ID < 70400) {
            $this->assertFalse(isset($config->noentry->host), 'can do isset checks on inaccessible parents');
            $this->assertNull($config['noentry']['host'], 'can do empty checks on inaccessible parents');
        }
        $this->assertFalse(isset($config['noentry']['host']), 'can do isset checks on inaccessible parents');

        // this avoids the "indirect" error
        $tmp = $config->group;
        $tmp['param3'] = 'value';
        $config->group = $tmp;
        $this->assertEquals('value', $config->group->param3);

        // but this is better:
        // set multilevel entries works
        $array['smtp']['host'] = 'localhost';
        Setup::set($array);
        $this->assertEquals(300, $config->issue_lock, 'the other entries are not lost');
        $this->assertEquals('localhost', $config->smtp->host, 'config change is present');

        $config = ServiceContainer::getConfig();
        $this->assertEquals('one', $config->item1, 'config change is present');
    }

    /**
     * @see Mail_Helper::getSMTPSettings did this weird settype:
     * settype($config['smtp']['auth'], 'boolean');
     * that does not work (Indirect modification error),
     * so test version that works
     */
    public function testSetType(): void
    {
        $config = ServiceContainer::getConfig();

        $config['smtp'] = [
            'from' => 'admin@example.org',
            'host' => 'localhost',
            'port' => '25',
            'auth' => '0',
        ];

        $this->assertSame('0', $config['smtp']['auth']);

        $config['smtp']['auth'] = (bool)$config['smtp']['auth'];
        $this->assertFalse($config['smtp']['auth']);
    }

    public function testArrayEmpty(): void
    {
        $setup = ServiceContainer::getConfig();

        // init
        $setup['email_reminder'] = [];

        // pre-requirements
        $this->assertNull($setup['email_reminder']['status']);
        $this->assertNull($setup['email_reminder']['addresses']);

        // check that this is false and does not trigger errors/notices
        $this->assertFalse($setup['email_reminder']['status'] === 'enabled' && $setup['email_reminder']['addresses']);

        $setup['email_reminder'] = [
            'status' => 'enabled',
            'aadresses' => [],
        ];
        // that empty addresses list is also false
        $this->assertFalse($setup['email_reminder']['status'] === 'enabled' && $setup['email_reminder']['addresses']);
    }

    public function testArrayMerge(): void
    {
        $defaults = [
            'email_routing' => [
                'warning' => [],
            ],
            'note_routing' => [],
            'draft_routing' => [],
            'subject_based_routing' => [],
        ];
        $config = [
            'email_routing' => [
                'recipient_type_flag' => 'Eventum',
                'flag_location' => 'before',
                'status' => 'enabled',
                'address_prefix' => 'issue-',
                'address_host' => 'eventum.example.org',
                'host_alias' => 'eventum.example.net',
                'warning' => [
                    'status' => 'disabled',
                ],
            ],
        ];
        Setup::set($defaults);
        Setup::set($config);

        $config = ServiceContainer::getConfig()->toArray();
        $this->assertEquals('enabled', $config['email_routing']['status']);
    }
}
