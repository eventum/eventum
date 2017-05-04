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

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

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
}
