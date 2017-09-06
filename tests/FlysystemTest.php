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

namespace Eventum\Test;

use DB_Helper;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use Phlib\Flysystem\Pdo\PdoAdapter;

class FlysystemTest extends TestCase
{
    public function testLocalAdapter()
    {
        $adapter = new Local(__DIR__ . '/data');
        $filesystem = new Filesystem($adapter);
        $path = __FUNCTION__ . '.txt';
        $contents = 'bite my shiny metal ass';

        $res = $filesystem->put($path, $contents);
        $this->assertTrue($res);

        $res = $filesystem->has($path);
        $this->assertTrue($res);

        $res = $filesystem->read($path);
        $this->assertEquals($res, $contents);
    }

    public function testPhlibFlysystemPdo()
    {
        /** @var PdoAdapter $db */
        $db = DB_Helper::getInstance();
        $pdo = $db->getPdo();

        $config = new Config([
            'table_prefix' => 'attachment',
            'enable_compression' => false,
            'chunk_size' => 1048576,
            'temp_dir' => '/var/tmp',
            'disable_mysql_buffering' => true,
        ]);

        $adapter = new PdoAdapter($pdo, $config);
        $filesystem = new Filesystem($adapter);

        $path = __FUNCTION__ . '.txt';
        $contents = 'bite my shiny metal ass';

        $res = $filesystem->put($path, $contents);
        $this->assertTrue($res);

        $res = $filesystem->has($path);
        $this->assertTrue($res);

        $res = $filesystem->read($path);
        $this->assertEquals($contents, $res);
    }
}
