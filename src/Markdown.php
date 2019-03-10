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

use cebe\markdown\GithubMarkdown;

class Markdown
{
    /** @var GithubMarkdown */
    private $parser;

    public function __construct()
    {
        $this->parser = new GithubMarkdown();
        $this->parser->enableNewlines = true;
    }

    public function render(string $text): string
    {
        $text = $this->parser->parse($text);
        // strip paragraph, confuses single line areas
        $text = preg_replace("{^<p>(.+)</p>\n$}", '$1', $text);

        return $text;
    }
}
