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

namespace Eventum\Test\TaskList;

use Eventum\TaskList\TaskListItem;
use Eventum\TaskList\TaskListMatcher;
use Eventum\Test\TestCase;

class TaskListTest extends TestCase
{
    public function test1(): void
    {
        $content = file_get_contents(__DIR__ . '/../data/tasklist.txt');
        $tm = new TaskListMatcher();
        $tasks = iterator_to_array($tm->getTasks($content));
        $this->assertCount(5, $tasks);

        $complete = array_filter(array_map(function (TaskListItem $c) {
            return $c->isChecked();
        }, $tasks));
        $this->assertCount(1, $complete);
    }
}
