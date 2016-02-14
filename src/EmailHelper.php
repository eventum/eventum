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

use Misc;
use Link_Filter;

class EmailHelper
{
    /**
     * @param string $text
     * @return string
     */
    public static function formatEmail($text)
    {
        $text = Misc::highlightQuotedReply($text);
        $text = nl2br($text);
        $text = Link_Filter::activateLinks($text);

        return $text;
    }
}
