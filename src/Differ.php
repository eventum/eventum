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

namespace Eventum;

use Text_Diff;
use Text_Diff_Renderer_unified;

class Differ
{
    public function diff(string $old, string $new): array
    {
        $params = [
            explode("\n", $old),
            explode("\n", $new),
        ];
        $differ = new Text_Diff('auto', $params);
        $renderer = new Text_Diff_Renderer_unified();

        return explode("\n", trim($renderer->render($differ)));
    }
}
