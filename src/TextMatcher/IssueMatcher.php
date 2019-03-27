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

class IssueMatcher
{
    /** @var TextMatcher */
    private $matcher;

    public function __construct(string $appUrl)
    {
        $baseUrl = preg_quote(rtrim($appUrl, '/'), '/');

        $regexp = "/
            (?P<text_1>(?i:issue):?\s\#?(?P<issue_id_1>\d+)) |
            (?P<text_2>{$baseUrl}\/view\.php\?id=(?P<issue_id_2>\d+))
        /x";
        $this->matcher = new TextMatcher($regexp, 2);
    }

    public function match(string $text): array
    {
        $result = [];
        foreach ($this->matcher->match($text) as $match) {
            $result[] = [
                'text' => $match['text'][0],
                'textOffset' => $match['text'][1],
                'issueId' => (int)$match['issue_id'][0],
            ];
        }

        return $result;
    }
}
