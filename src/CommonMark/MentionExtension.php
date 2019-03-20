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

namespace Eventum\CommonMark;

use League\CommonMark\Extension\Extension;
use League\CommonMark\Inline\Parser\InlineParserInterface;

class MentionExtension extends Extension
{
    /**
     * @return InlineParserInterface[]
     */
    public function getInlineParsers(): array
    {
        $lookup = new UserLookup();
        $linkPattern = APP_BASE_URL . 'list.php?reporter=%s&hide_closed=1';

        return [
            new InlineMentionParser($linkPattern, $lookup),
        ];
    }

    public function getName(): string
    {
        return 'mention';
    }
}
