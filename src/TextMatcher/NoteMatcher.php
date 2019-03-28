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

namespace Eventum\TextMatcher;

use Generator;
use Note;

class NoteMatcher implements TextMatchInterface
{
    /** @var TextMatcher */
    private $matcher;

    public function __construct(string $appUrl)
    {
        $baseUrl = preg_quote(rtrim($appUrl, '/'), '/');

        $regexp = "/
            (?P<text_1>{$baseUrl}\/view_note\.php\?id=(?P<note_id_1>\d+))
        /x";
        $this->matcher = new TextMatcher($regexp);
    }

    public function match(string $text): Generator
    {
        foreach ($this->matcher->match($text) as $match) {
            $noteId = (int)$match['note_id'][0];
            yield [
                'text' => $match['text'][0],
                'textOffset' => $match['text'][1],
                'noteId' => $noteId,
                'issueId' => (int)Note::getIssueID($noteId),
            ];
        }
    }
}
