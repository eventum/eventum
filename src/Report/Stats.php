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

namespace Eventum\Report;

use Math_Stats;

class Stats
{
    public function getStats(array $input): array
    {
        $stats = new Math_Stats();
        $stats->setData($input);

        return [
            'total' => $stats->sum(),
            'avg' => $stats->mean(),
            'median' => $stats->median(),
            'max' => $stats->max(),
        ];
    }
}
