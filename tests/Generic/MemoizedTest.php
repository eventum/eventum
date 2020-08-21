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

namespace Eventum\Test\Generic;

use Eventum\MemoizeDecorator;
use Eventum\Test\TestCase;

class MemoizedTest extends TestCase
{
    public function test1(): void
    {
        /** @var MemoizedTestClass $instance */
        $instance = new MemoizeDecorator(new MemoizedTestClass());
        $this->assertEquals('00', $instance->getFoo());
        $this->assertEquals('00', $instance->getFoo());
        $this->assertEquals('21', $instance->getFoo(2));

        // different instance, different cache
        $instance = new MemoizeDecorator(new MemoizedTestClass());
        // but $i is global static, so it's still incremented
        $this->assertEquals('02', $instance->getFoo());
    }
}

class MemoizedTestClass
{
    public function getFoo($a = 0)
    {
        static $i = 0;

        return $a . $i++;
    }
}
