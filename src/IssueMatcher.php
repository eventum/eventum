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

use RuntimeException;

class IssueMatcher
{
    /** @var string */
    private $regexp;
    /** @var int */
    private $maxGroups = 2;

    public function __construct(string $appUrl)
    {
        $baseUrl = preg_quote(rtrim($appUrl, '/'), '/');

        $this->regexp = "/
            (?P<text_1>(?i:issue):?\s\#?(?P<issue_id_1>\d+)) |
            (?P<text_2>{$baseUrl}\/view\.php\?id=(?P<issue_id_2>\d+))
        /x";
    }

    public function match(string $message): ?array
    {
        if (preg_match_all($this->regexp, $message, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            throw new RuntimeException('Failed to match expression');
        }

        $result = [];
        foreach ($matches as $match) {
            for ($text = null, $issue_id = null, $i = 1; $i <= $this->maxGroups; $i++) {
                if (!isset($match["text_$i"][0])) {
                    continue;
                }

                $text = $match["text_$i"][0] ? $match["text_$i"] : null;
                $issue_id = $match["issue_id_$i"][0] ? $match["issue_id_$i"] : null;
            }

            $result[] = [
                'text' => $text[0],
                'textOffset' => $text[1],
                'issueId' => (int)$issue_id[0],
            ];
        }

        return $result;
    }
}
