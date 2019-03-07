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

use Auth;
use EmailReplyParser\EmailReplyParser;
use EmailReplyParser\Fragment;
use EmailReplyParser\Parser\EmailParser;
use Eventum\Db\Doctrine;
use Link_Filter;
use Misc;

class EmailHelper
{
    /**
     * Signature regexp, without greedy "^-\w" pattern
     * https://github.com/willdurand/EmailReplyParser/pull/42
     */
    private const SIG_REGEX = '/(?:^\s*__|^-- $)|(?:^Sent from my (?:\s*\w+){1,3})$/s';

    /**
     * Format email body in view emails/view notes popup and expandable view
     */
    public static function formatEmail(string $text): string
    {
        static $enabled;

        if ($enabled === null) {
            $usr_id = Auth::getUserID();
            $prefs = Doctrine::getUserPreferenceRepository()->findOrCreate($usr_id);
            $enabled = $prefs->isMarkdownEnabled();
        }

        $text = self::collapseReplies($text, $enabled);

        if (!Link_Filter::markdownEnabled()) {
            $text = nl2br($text);
            $text = Link_Filter::activateLinks($text);
        }

        return $text;
    }

    /**
     * Collapse email replies and signatures into expandable block
     */
    private static function collapseReplies(string $text, bool $enabled): string
    {
        if (!$enabled) {
            return Misc::highlightQuotedReply($text);
        }

        $wrapText = function ($text) {
            if (Link_Filter::markdownEnabled()) {
                $text = Link_Filter::markdownFormat($text);
            } else {
                $text = Misc::highlightQuotedReply($text);
            }

            return
                '<div><span class="toggle-trimmed-email"><a href="#">…</a></span>'
                . '<div class="hidden email-trimmed">'
                . $text
                . '</div></div>';
        };

        static $parser;
        if (!$parser) {
            $parser = new EmailParser();
            $parser->setSignatureRegex(self::SIG_REGEX);
        }
        $email = $parser->parse($text);

        // wrap quoted and signature blocks with a div
        $blocks = array_map(
            function (Fragment $fragment) use ($wrapText) {
                if ($fragment->isQuoted() || $fragment->isSignature()) {
                    return $wrapText($fragment);
                }

                if (Link_Filter::markdownEnabled()) {
                    return Link_Filter::markdownFormat((string)$fragment);
                }

                return htmlspecialchars($fragment);
            }, $email->getFragments()
        );
        $text = rtrim(implode("\n", $blocks));

        return $text;
    }
}
