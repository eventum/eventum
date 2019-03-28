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
use RuntimeException;

class TextMatcher implements TextMatchInterface
{
    /** @var string */
    private $pattern;
    /** @var int */
    private $maxGroups = 2;

    public function __construct(string $pattern, int $maxGroups = 1)
    {
        $this->pattern = $pattern;
        $this->maxGroups = $maxGroups;
    }

    public function match(string $message): Generator
    {
        if (preg_match_all($this->pattern, $message, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            throw new RuntimeException('Failed to match expression');
        }

        foreach ($matches as $match) {
            for ($i = 1; $i <= $this->maxGroups; $i++) {
                if (!isset($match["text_$i"][0]) || $match["text_$i"][1] === -1) {
                    continue;
                }

                yield iterator_to_array($this->filterKeys($match));
            }
        }
    }

    /**
     * Filter keys, to return only string keys and numeric suffix removed
     */
    private function filterKeys(array $array): Generator
    {
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $key = preg_replace('/_\d+$/', '', $key);

            yield $key => $value;
        }
    }
}
