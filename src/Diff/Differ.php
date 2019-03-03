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

use SebastianBergmann\Diff;

class Differ
{
    private $differ;

    public function __construct()
    {
        $builder = new Diff\Output\UnifiedDiffOutputBuilder('', true);
        $this->differ = new Diff\Differ($builder);
    }

    public function diff(string $old, string $new): array
    {
        $diff = $this->differ->diff($old, $new);

        return explode("\n", trim($diff));
    }
}
