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

class TaskListItem
{
    private const COMPLETE = '/\[[xX]\]/';

    /** @var string */
    private $checkBox;
    /** @var string */
    private $label;

    public function __construct(string $checkBox, string $label)
    {
        $this->checkBox = $checkBox;
        $this->label = $label;
    }

    /**
     * @see https://github.com/github/task_list/blob/v1.0.2/lib/task_list.rb#L26-L31
     * @return bool
     */
    public function isChecked(): bool
    {
        return (bool)preg_match(self::COMPLETE, $this->checkBox);
    }
}
