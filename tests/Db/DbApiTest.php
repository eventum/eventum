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
use Eventum\Db\Adapter\NullAdapter;
use Eventum\Test\TestCase;

/**
 * @group db
 */
class DbApiTest extends TestCase
{
    public function testNullApi(): void
    {
        $config = DB_Helper::getConfig();
        $instance = new NullAdapter($config);
        $this->assertNotNull($instance);
    }
}
