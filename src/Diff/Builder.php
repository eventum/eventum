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

namespace Eventum\Diff;

class Builder
{
    /** @var array */
    private $buffer = [];

    /** @var Differ */
    private $differ;

    public function __construct()
    {
        $this->differ = new Differ();
    }

    public function addChange(string $prefix, string $old, string $new): self
    {
        $this->buffer[] = "-{$prefix}: {$old}";
        $this->buffer[] = "+{$prefix}: {$new}";

        return $this;
    }

    public function addTextChange(string $prefix, $old, $new): self
    {
        $this->buffer[] = "{$prefix}:";
        foreach ($this->differ->diff($old, $new) as $line) {
            $this->buffer[] = $line;
        }
        $this->buffer[] = '';

        return $this;
    }

    public function getChanges(): array
    {
        return $this->buffer;
    }
}
