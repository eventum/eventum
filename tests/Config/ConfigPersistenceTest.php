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

use Eventum\Config\ConfigPersistence;
use Eventum\Test\TestCase;

class ConfigPersistenceTest extends TestCase
{
    /** @var ConfigPersistence */
    private $handler;

    public function setUp(): void
    {
        $this->handler = new ConfigPersistence();
    }

    public function testLoadStore(): void
    {
        $configDir = sys_get_temp_dir() . '/eventum_test';
        $configFile = $configDir . '/setup.php';

        $config = $this->handler->load($configFile);
        $this->assertIsArray($config, 'Loading missing file yields empty config');

        $this->handler->store($configFile, $config);
        $contents = $this->readFile($configFile);
        $expected = implode('', [ConfigPersistence::PREFIX, '[]', ConfigPersistence::SUFFIX]);
        $this->assertEquals($expected, $contents, 'Stored config is valid PHP script');
    }
}
