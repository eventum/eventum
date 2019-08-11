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
use Setup;

class GroupMatcher implements TextMatchInterface
{
    /** @var TextMatchInterface[] */
    private $matchers;

    public function __construct(array $matchers)
    {
        $this->matchers = $matchers;
    }

    public static function create(): self
    {
        $appUrl = Setup::getBaseUrl();

        return new self([
            new IssueMatcher($appUrl),
            new NoteMatcher($appUrl),
        ]);
    }

    public function match(string $text): Generator
    {
        // this delegates generator,
        // should instead sort by textOffset value?
        foreach ($this->matchers as $matcher) {
            yield from $matcher->match($text);
        }
    }
}
