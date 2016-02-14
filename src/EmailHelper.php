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

use EmailReplyParser\EmailReplyParser;
use EmailReplyParser\Fragment;
use Link_Filter;
use Misc;

class EmailHelper
{
    /**
     * Format email body in view emails/view notes popup and expandable view
     *
     * @param string $text
     * @return string
     */
    public static function formatEmail($text)
    {
        $text = self::collapseReplies($text);
        $text = nl2br($text);
        $text = Link_Filter::activateLinks($text);

        return $text;
    }

    /**
     * Collapse email replies and signatures into expandable block
     *
     * @param string $text
     * @return string
     */
    private static function collapseReplies($text)
    {
        $wrapText = function ($text) {
            $text = Misc::highlightQuotedReply($text);

            return
                '<img src="images/blank.gif" class="toggle-trimmed-email"><div class="hidden email-trimmed">'
                . $text . '</div>';
        };

        $email = EmailReplyParser::read($text);

        // wrap quoted and signature blocks with a div
        $blocks = array_map(
            function (Fragment $fragment) use ($wrapText) {
                if ($fragment->isQuoted() || $fragment->isSignature()) {
                    return $wrapText($fragment);
                }

                return $fragment;

            }, $email->getFragments()
        );
        $text = rtrim(implode("\n", $blocks));

        return $text;
    }
}
