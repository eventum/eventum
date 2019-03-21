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

use MathPHP\Statistics\Average;

class Stats
{
    public function getStats(array $numbers): array
    {
        return [
            'total' => array_sum($numbers),
            'avg' => Average::mean($numbers),
            'median' => Average::median($numbers),
            'max' => max($numbers),
        ];
    }
}
