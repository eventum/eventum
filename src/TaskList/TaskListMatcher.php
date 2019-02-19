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

namespace Eventum\TaskList;

use Generator;
use RuntimeException;

/**
 * Contains functionality for objects that can have task lists in their
 * descriptions.
 *
 * Task list items can be added with Markdown like "* [x] Fix bugs".
 *
 * @see https://gitlab.com/gitlab-org/gitlab-ce/blob/v11.7.5/app/models/concerns/taskable.rb
 */
class TaskListMatcher
{
    private const ITEM_PATTERN = '{
		^
		\s*(?:[-+*]|(?:\d+\.))  # list prefix required - task item has to be always in a list
		\s+                     # whitespace prefix has to be always presented for a list item
		(\[\s\]|\[[xX]\])       # checkbox
		(\s.+)                  # followed by whitespace and some text.
    }xm';

    public function getTasks(string $content): Generator
    {
        if (preg_match_all(self::ITEM_PATTERN, $content, $matches, PREG_SET_ORDER) === false) {
            throw new RuntimeException('Failed to match expression');
        }

        foreach ($matches as $match) {
            # ITEM_PATTERN strips out the hyphen, but Item requires it. Rabble rabble.
            yield new TaskListItem("- {$match[1]}", trim($match[2]));
        }
    }
}
